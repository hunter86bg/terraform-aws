# terraform-aws
Deploy wordpress in AWS


## Prerequisites
- Terraform v0.14.9
- aws-cli v2.1.34
- Git
- Access to AWS Secrets Manager

## Provisioning

```
git clone https://github.com/hunter86bg/terraform-aws.git
cd terraform-aws
terraform apply
```
Please wait for at least a couple of minutes before using the web address provided by terraform.

## Cleanup
```
terraform destroy
```

## To-Do:
- restructure the terraform code into separate entities
- rename the terraform objects to something more useful
