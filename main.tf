terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 3.35"
    }
  }
}

provider "aws" {
  profile = "default"
  region = "us-east-2"
}

resource "aws_key_pair" "deployer" {
  key_name   = "penguin"
  public_key = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDo5k+g9BGKsp9miCij2KnLxpwskd5FPQQn1BaowQb4GIA1kSApBWs5ocfxstOZStvA/GrQX8TJXFoPwQ12LHoJRVzEFhGnPCHaQMOMoJZ5OVhhGLeG5WE1V7rMu4ASilPqEJFPoMmgTrdCu+j/1K0pnVF7jkjRiXPc+Ck5Vw7ZF/OOK7zQCPHk56CrMT5a4HgD47Si1vZ08Sr7FGg9D/TJepMnpjpeaM00/YAxkUo7o9UoHokP9ugEeYCI3EJWRjduSPOIzmmZigRGZbbtQaj+E3izTotGxOQI5gRkxJFfxhDnXMTeV9IIV1i12lFnbZlvhghTXp/3fqT0OsS1JAQn root@penguin"
}


resource "aws_launch_configuration" "example" {
 image_id  = "ami-089fe97bc00bff7cc"
 instance_type = "t2.micro"
 security_groups = [aws_security_group.instance.id, aws_security_group.ssh.id]
 key_name = aws_key_pair.deployer.key_name
 user_data = templatefile("${path.module}/wordpress.tpl", { dbaddr= aws_db_instance.example.address, dbpass = local.db_creds.password, dbuser = local.db_creds.username,  dbname = aws_db_instance.example.name, alb_dns = aws_lb.example.dns_name } )

 depends_on = [aws_db_instance.example]

 # Required when using a launch configuration with an auto scaling group.
 # https://www.terraform.io/docs/providers/aws/r/launch_configuration.html
 lifecycle {
 create_before_destroy = true
 }
}





resource "aws_security_group" "instance" {
 name = "terraform-example-instance"
 ingress {
 	from_port = var.server_port
 	to_port = var.server_port
 	protocol = "tcp"
 	cidr_blocks = ["0.0.0.0/0"]
 	}
 ingress {
	from_port = 22
	to_port = 22
	protocol = "tcp"
	cidr_blocks = ["213.214.92.205/32"]
	}
 egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_security_group" "ssh" {
 name = "terraform-ssh"
 ingress {
       from_port = 22
       to_port = 22
       protocol = "tcp"
       cidr_blocks = ["213.214.92.205/32"]
       }
}

data "aws_vpc" "default" {
 default = true
}

data "aws_subnet_ids" "default" {
 vpc_id = data.aws_vpc.default.id
}



variable "server_port" {
 description = "The port the server will use for HTTP requests"
 type = number
 default = 80
}

resource "aws_autoscaling_group" "example" {
 launch_configuration = aws_launch_configuration.example.name
 vpc_zone_identifier = data.aws_subnet_ids.default.ids

 target_group_arns = [aws_lb_target_group.asg.arn]
 health_check_type = "ELB"

 min_size = 2
 max_size = 2

 tag {
 key = "Name"
 value = "terraform-asg-example"
 propagate_at_launch = true
 }
}


resource "aws_lb" "example" {
 name = "terraform-asg-example"
 load_balancer_type = "application"
 subnets = data.aws_subnet_ids.default.ids
 security_groups = [aws_security_group.instance.id, aws_security_group.ssh.id]
}

resource "aws_lb_listener" "http" {
 load_balancer_arn = aws_lb.example.arn
 port = var.server_port
 protocol = "HTTP"
# By default, return a simple 404 page
 default_action {
 type = "fixed-response"
 fixed_response {
 content_type = "text/plain"
 message_body = "404: page not found"
 status_code = 404
 }
 }
}

resource "aws_security_group" "alb" {
 name = "terraform-example-alb"
 # Allow inbound HTTP requests
 ingress {
 from_port = var.server_port
 to_port = var.server_port
 protocol = "tcp"
 cidr_blocks = ["0.0.0.0/0"]
 }
 # Allow all outbound requests
 egress {
 from_port = 0
 to_port = 0
 protocol = "-1"
 cidr_blocks = ["0.0.0.0/0"]
 }
}

resource "aws_security_group" "rds" {
 name = "rds-access-control"
 # Allow inbound  requests
 ingress {
 from_port = 3306
 to_port = 3306
 protocol = "tcp"
 cidr_blocks = ["0.0.0.0/0"]
 }
 # Allow all outbound requests
 egress {
 from_port = 0
 to_port = 0
 protocol = "-1"
 cidr_blocks = ["0.0.0.0/0"]
 }
}


resource "aws_lb_target_group" "asg" {
 name = "terraform-asg-example"
 port = var.server_port
 protocol = "HTTP"
 vpc_id = data.aws_vpc.default.id
# health_check {
# path = "/"
# protocol = "HTTP"
# matcher = "200"
# interval = 120
# timeout = 30
# healthy_threshold = 2
# unhealthy_threshold = 2
# }
}


resource "aws_lb_listener_rule" "asg" {
 listener_arn = aws_lb_listener.http.arn
 priority = 100
 condition {
 path_pattern {
  values = ["*"]
  }
 }
 action {
 type = "forward"
 target_group_arn = aws_lb_target_group.asg.arn
 }
}


resource "aws_db_instance" "example" {
 identifier_prefix = "terraform-up-and-running"
 vpc_security_group_ids = [ aws_security_group.rds.id ]
 engine = "mysql"
 allocated_storage = 10
 instance_class = "db.t2.micro"
 name = "example_database"
 publicly_accessible = false
 username = local.db_creds.username
 password = local.db_creds.password
 skip_final_snapshot = true
}


data "aws_secretsmanager_secret_version" "creds" {
  # Fill in the name you gave to your secret
  secret_id  = "terrapass2"
}

locals {
  db_creds = jsondecode(
    data.aws_secretsmanager_secret_version.creds.secret_string
    )
}

output "alb_dns_name" {
 value = "Wordpress can be accessed over http://${aws_lb.example.dns_name}/"
 description = "The domain name of the load balancer"
}


#output "address" {
# value = aws_db_instance.example.address
# description = "Connect to the database at this endpoint"
#}
#
#output "port" {
# value = aws_db_instance.example.port
# description = "The port the database is listening on"
#}
