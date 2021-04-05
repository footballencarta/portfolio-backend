variable "destination_address" {
    type = string
}

variable "source_address" {
    type = string
}

resource "aws_ssm_parameter" "cfnRole" {
    name = "portfolio-backend-cfnRole"
    description = "Cloudformation role for portfolio backend"
    type = "String"
    value = aws_iam_role.cloudformation_role.arn
}

resource "aws_ssm_parameter" "lambdaRole" {
    name = "portfolio-backend-lambdaRole"
    description = "Lambda role for portfolio backend"
    type = "String"
    value = aws_iam_role.lambda_role.arn
}

resource "aws_ssm_parameter" "serverlessDeployBucket" {
    name = "serverless-deploy-bucket"
    description = "Serverless Deployment bucket"
    type = "String"
    value = aws_s3_bucket.serverless_deploy_bucket.id
}

resource "aws_ssm_parameter" "appKey" {
    name = "portfolio-backend-app-key"
    description = "APP_KEY for portfolio backend"
    type = "SecureString"
    value = random_id.app_key.b64_std
}

resource "aws_ssm_parameter" "destinationAddress" {
    name = "portfolio-backend-destination-address"
    description = "DESTINATION_ADDRESS for portfolio backend"
    type = "SecureString"
    value = var.destination_address
}

resource "aws_ssm_parameter" "senderAddress" {
    name = "portfolio-backend-sender-address"
    description = "SENDER_ADDRESS for portfolio backend"
    type = "SecureString"
    value = var.source_address
}

resource "random_id" "app_key" {
    byte_length = 32
}
