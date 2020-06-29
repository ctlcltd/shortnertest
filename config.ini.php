<?php
[Host]
htbackenddomain = "third.domain.ltd"
htssr = true
ht404 = ""
ht50x = ""

[Backend]
; abs path
bepath = "./backend"

[Database]
; abs path
dbdsn = "sqlite:../db/db.sqlite"
dbuser = ""
dbpass = ""
dbopts[empty] =
dbshadow = true

[Network]
nwsetup = false
nwuseracl = "store,domains"
nwuseractionlifetime = 259200
