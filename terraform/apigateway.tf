variable "domain_name" {
    type = string
}

variable "hosted_zone_id" {
    type = string
}

variable "allow_origins" {
    type = set(string)
}

resource "aws_acm_certificate" "portfolio_cert" {
    domain_name       = var.domain_name
    validation_method = "DNS"

    lifecycle {
        create_before_destroy = true
    }
}

resource "aws_apigatewayv2_api" "http_gateway" {
    name          = "portfolio-site-http-api"
    protocol_type = "HTTP"
    cors_configuration {
        allow_origins = var.allow_origins
        allow_methods = ["POST"]
    }
}

resource "aws_apigatewayv2_stage" "http_gateway_default_stage" {
    api_id      = aws_apigatewayv2_api.http_gateway.id
    name        = "$default"
    auto_deploy = true
}

resource "aws_apigatewayv2_domain_name" "portfolio_backend_domain" {
    domain_name = var.domain_name

    domain_name_configuration {
        certificate_arn = aws_acm_certificate.portfolio_cert.arn
        endpoint_type   = "REGIONAL"
        security_policy = "TLS_1_2"
    }
}

resource "aws_route53_record" "portfolio_backend_domain" {
    name    = aws_apigatewayv2_domain_name.portfolio_backend_domain.domain_name
    type    = "A"
    zone_id = var.hosted_zone_id

    alias {
        name                   = aws_apigatewayv2_domain_name.portfolio_backend_domain.domain_name_configuration[0].target_domain_name
        zone_id                = aws_apigatewayv2_domain_name.portfolio_backend_domain.domain_name_configuration[0].hosted_zone_id
        evaluate_target_health = false
    }
}

resource "aws_apigatewayv2_api_mapping" "portfolio_backend_domain" {
    api_id      = aws_apigatewayv2_api.http_gateway.id
    domain_name = aws_apigatewayv2_domain_name.portfolio_backend_domain.id
    stage       = aws_apigatewayv2_stage.http_gateway_default_stage.id
}
