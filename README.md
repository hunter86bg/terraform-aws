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
Use the web address provided as "alb_dns_name" to access the Wordpress Site.

## Cleanup
```
terraform destroy
```
