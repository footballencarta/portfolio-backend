resource "aws_ssm_parameter" "cfnRole" {
  name        = "portfolio-backend-cfnRole"
  description = "Cloudformation role for portfolio backend"
  type        = "String"
  value       = aws_iam_role.cloudformation_role.arn
}

resource "aws_ssm_parameter" "lambdaRole" {
  name        = "portfolio-backend-lambdaRole"
  description = "Lambda role for portfolio backend"
  type        = "String"
  value       = aws_iam_role.lambda_role.arn
}

resource "aws_ssm_parameter" "serverlessDeployBucket" {
  name        = "serverless-deploy-bucket"
  description = "Serverless Deployment bucket"
  type        = "String"
  value       = aws_s3_bucket.serverless_deploy_bucket.id
}