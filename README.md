# terraform-aws
Deploy wordpress in AWS


## Prerequisites
- Terraform v0.14.9
- Git
- Access to AWS Secrets Manager

## Provisioning

```
git clone https://github.com/hunter86bg/terraform-aws.git
cd terraform-aws
terraform apply
```
Wait several minutes before using the web address provided by terraform.

## Cleanup
```
terraform destroy
```
