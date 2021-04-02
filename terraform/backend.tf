terraform {
    backend "s3" {
        bucket = "dw-terraform-deploys"
        key    = "portfolio-backend"
        region = "eu-west-2"
    }
}