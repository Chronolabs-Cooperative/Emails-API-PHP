
CREATE PROCEEDURE uiVerifyAddress
(
	IN @status VARCHAR(64),
	IN @name VARCHAR(255), 
	IN @email VARCHAR(196)
)
BEGIN
  IF EXISTS (SELECT * FROM `verify_address` where `email` LIKE '@email') THEN
    UPDATE `verify_address` SET `status`= '@status', `name`= '@name' WHERE `email` = '@email';
  ELSE 
    INSERT INTO `verify_address` (`status`,`name`,`email`,`created`) values ('@status','@name','@email',UNIX_TIMESTAMP());
  END IF;
END