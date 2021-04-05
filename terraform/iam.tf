resource "aws_iam_role" "deployment_role" {
    name = "backend_deployment_role"

    assume_role_policy = jsonencode({
        Version = "2012-10-17"

        Statement = [
            {
                Action = "sts:AssumeRole"
                Effect = "Allow"
                Principal = {
                    AWS = "arn:aws:iam::845057874985:root"
                }
            }
        ]
    })

    inline_policy {
        name = "deployment_role"

        policy = jsonencode({
            Version = "2012-10-17"
            Statement = [
                {
                    Effect = "Allow"
                    Action = [
                        "iam:PassRole",
                    ]
                    Resource = aws_iam_role.lambda_role.arn
                },
                {
                    Effect = "Allow"
                    Action = [
                        "iam:PassRole",
                    ]
                    Resource = aws_iam_role.cloudformation_role.arn
                },
                {
                    Effect = "Allow"
                    Action = [
                        "s3:ListAllMyBuckets",
                        "s3:CreateBucket"
                    ]
                    Resource = "*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "s3:*",
                    ]
                    Resource = [
                        "arn:aws:s3:::portfolio-backend*",
                        "arn:aws:s3:::portfolio-backend*/*"
                    ]
                },
                {
                    Effect = "Allow"
                    Action = [
                        "s3:ListAllMyBuckets",
                        "s3:CreateBucket"
                    ]
                    Resource = "*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "cloudformation:CreateStack",
                        "cloudformation:UpdateStack",
                        "cloudformation:DeleteStack"
                    ]
                    Resource = "arn:aws:cloudformation:eu-west-2:*:stack/portfolio-backend*/*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "cloudformation:Describe*",
                        "cloudformation:List*",
                        "cloudformation:Get*",
                        "cloudformation:PreviewStackUpdate",
                        "cloudformation:ValidateTemplate"
                    ]
                    Resource = "*"
                }
            ]
        })
    }
}

resource "aws_iam_role" "cloudformation_role" {
    name = "backend_cloudformation_role"

    assume_role_policy = jsonencode({
        Version = "2012-10-17"

        Statement = [
            {
                Action = "sts:AssumeRole"
                Effect = "Allow"
                Principal = {
                    Service = "cloudformation.amazonaws.com"
                }
            }
        ]
    })

    inline_policy {
        name = "cloudformation_role"

        policy = jsonencode({
            Version = "2012-10-17"
            Statement = [
                {
                    Effect = "Allow"
                    Action = [
                        "iam:PassRole",
                    ]
                    Resource = aws_iam_role.lambda_role.arn
                },
                {
                    Effect = "Allow"
                    Action = [
                        "cloudformation:Describe*",
                        "cloudformation:List*",
                        "cloudformation:Get*",
                        "cloudformation:PreviewStackUpdate",
                        "cloudformation:CreateStack",
                        "cloudformation:UpdateStack"
                    ]
                    Resource = "arn:aws:cloudformation:eu-west-2:*:stack/portfolio-backend*/*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "cloudformation:ValidateTemplate"
                    ]
                    Resource = "*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "s3:*",
                    ]
                    Resource = [
                        "arn:aws:s3:::portfolio-backend*",
                        "arn:aws:s3:::portfolio-backend*/*"
                    ]
                },
                {
                    Effect = "Allow"
                    Action = [
                        "apigateway:GET",
                        "apigateway:HEAD",
                        "apigateway:OPTIONS",
                        "apigateway:PATCH",
                        "apigateway:POST",
                        "apigateway:PUT",
                        "apigateway:DELETE"
                    ]
                    Resource = [
                        "arn:aws:apigateway:eu-west-2::/apis",
                        "arn:aws:apigateway:eu-west-2::/apis/*"
                    ]
                },
                {
                    Effect = "Allow"
                    Action = [
                        "logs:DescribeLogGroups"
                    ]
                    Resource = "arn:aws:logs:eu-west-2:*:log-group::log-stream:*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "logs:CreateLogGroup",
                        "logs:CreateLogStream",
                        "logs:DeleteLogGroup",
                        "logs:DeleteLogStream",
                        "logs:DescribeLogStreams",
                        "logs:FilterLogEvents"
                    ]
                    Resource = "arn:aws:logs:eu-west-2:*:log-group:/aws/lambda/portfolio-backend*:log-stream:*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "lambda:GetFunction",
                        "lambda:CreateFunction",
                        "lambda:DeleteFunction",
                        "lambda:UpdateFunctionConfiguration",
                        "lambda:UpdateFunctionCode",
                        "lambda:ListVersionsByFunction",
                        "lambda:PublishVersion",
                        "lambda:CreateAlias",
                        "lambda:DeleteAlias",
                        "lambda:UpdateAlias",
                        "lambda:GetFunctionConfiguration",
                        "lambda:AddPermission",
                        "lambda:RemovePermission",
                        "lambda:InvokeFunction",
                        "lambda:ListTags",
                        "lambda:TagResource",
                        "lambda:UntagResource",
                        "lambda:DeleteEventSourceMapping"
                    ]
                    Resource = "arn:aws:lambda:eu-west-2:*:function:portfolio-backend*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "lambda:GetLayerVersion",
                        "lambda:CreateEventSourceMapping",
                        "lambda:GetEventSourceMapping"
                    ]
                    Resource = "*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "dynamodb:*"
                    ]
                    Resource = [
                        "arn:aws:dynamodb:eu-west-2:*:table/portfolio-backend-*",
                        "arn:aws:dynamodb:eu-west-2:*:table/portfolio-backend-*/stream/*"
                    ]
                }
            ]
        })
    }
}

resource "aws_iam_role" "lambda_role" {
    name = "backend_lambda_role"

    assume_role_policy = jsonencode({
        Version = "2012-10-17"

        Statement = [
            {
                Action = "sts:AssumeRole"
                Effect = "Allow"
                Principal = {
                    Service = "lambda.amazonaws.com"
                }
            }
        ]
    })

    inline_policy {
        name = "lambda_role"

        policy = jsonencode({
            Version = "2012-10-17"
            Statement = [
                {
                    Effect = "Allow"
                    Action = [
                        "logs:CreateLogStream",
                        "logs:CreateLogGroup"
                    ]
                    Resource = "arn:aws:logs:eu-west-2:*:log-group:/aws/lambda/portfolio-backend-dev*:*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "logs:PutLogEvents"
                    ]
                    Resource = "arn:aws:logs:eu-west-2:*:log-group:/aws/lambda/portfolio-backend-dev*:*:*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "dynamodb:PutItem",
                        "dynamodb:UpdateItem"
                    ]
                    Resource = "arn:aws:dynamodb:eu-west-2:*:table/portfolio-backend-*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "dynamodb:GetRecords",
                        "dynamodb:GetShardIterator",
                        "dynamodb:DescribeStream",
                        "dynamodb:ListShards",
                        "dynamodb:ListStreams"
                    ]
                    Resource = "arn:aws:dynamodb:eu-west-2:*:table/portfolio-backend-*/stream/*"
                },
                {
                    Effect = "Allow"
                    Action = [
                        "ses:sendEmail"
                    ]
                    Resource = "*"
                },
            ]
        })
    }
}
