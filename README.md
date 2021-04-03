# Portfolio Backend
[![Build Status](https://www.travis-ci.com/footballencarta/portfolio-backend.svg?branch=main)](https://www.travis-ci.com/footballencarta/portfolio-backend) [![codecov](https://codecov.io/gh/footballencarta/portfolio-backend/branch/main/graph/badge.svg?token=TNUFC8R5HQ)](https://codecov.io/gh/footballencarta/portfolio-backend) [![Maintainability](https://api.codeclimate.com/v1/badges/822b56afaa8eef1af1fb/maintainability)](https://codeclimate.com/github/footballencarta/portfolio-backend/maintainability) [![StyleCI](https://github.styleci.io/repos/354085678/shield?branch=main)](https://github.styleci.io/repos/354085678?branch=main)

Backend for my portfolio site on damonwilliams.co.uk

Technical choices:

* **PHP**: I've been using PHP since 2006, so I know it pretty well.
* **Serverless Framework**: Serverless Framework allows the API to run on AWS Lambda. This means it's now (almost) infinitely scalable, with consistent response times.
* **NodeJS**: Serverless runs on NodeJS, so is a required dependency
* **Bref.sh**: PHP Layer for AWS Lambda allowing PHP to be ran natively.
* **PHPUnit**: Unit testing, as that's pretty important.
* **PHPCS**: Code sniffer to make sure the code adheres to the [PSR-12 standard](https://www.php-fig.org/psr/psr-12/)
* **PHPStan**: Static Analysis to make help spot those pesky bugs earlier.
* **[iamlive](https://github.com/iann0036/iamlive)**: To record minimum IAM permissions required for deployment.
* **Terraform**: To create the role and deployment bucket ahead of time to allow the Lambda deployments to follow the Principle of least privilege
