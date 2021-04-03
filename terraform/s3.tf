resource "aws_s3_bucket" "serverless_deploy_bucket" {
    bucket = "portfolio-backend-deploys"
    acl = "private"
}
