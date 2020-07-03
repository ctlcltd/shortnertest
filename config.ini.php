<?php
[Host]
ssr = true
error_404 = ""
error_50x = ""
; abs path
backend_path = "./backend"

[Database]
; abs path
dsn = "sqlite:../db/db.sqlite"
username = ""
password = ""
options[empty] =
shadow = true

[Network]
setup = false
api_test = true
user_acl = "store,domains"
user_action_lifetime = 259200
