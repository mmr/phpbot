UPDATE user SET
  usr_quit_dt = now(),
  usr_quit_qtt = usr_quit_qtt + 1
WHERE
  usr_join_qtt = (usr_quit_qtt + 1);

UPDATE user SET
usr_max_period = UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(usr_join_dt)
WHERE
  usr_join_qtt = usr_quit_qtt AND 
  usr_max_period < UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(usr_join_dt);
