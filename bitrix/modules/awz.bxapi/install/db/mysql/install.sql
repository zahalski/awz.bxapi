create table if not exists b_awz_bxapi_tokens (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `PORTAL` varchar(65) NOT NULL,
    `APP_ID` varchar(65) NOT NULL,
    `ACTIVE` varchar(1) NOT NULL,
    `PARAMS` varchar(6255) NOT NULL,
    `TOKEN` varchar(1255) NOT NULL,
    `EXPIRED_TOKEN` datetime NOT NULL,
    `EXPIRED_REFRESH` datetime NOT NULL,
    PRIMARY KEY (`ID`)
);
create table if not exists b_awz_bxapi_apps (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `NAME` varchar(65) NOT NULL,
    `PORTAL` varchar(65) NOT NULL,
    `APP_ID` varchar(65) NOT NULL,
    `ACTIVE` varchar(1) NOT NULL,
    `PARAMS` varchar(6255) NOT NULL,
    `TOKEN` varchar(1255) NOT NULL,
    `DATE_ADD` datetime NOT NULL,
    PRIMARY KEY (`ID`),
    unique IX_APP_ID (APP_ID)
);
CREATE TABLE IF NOT EXISTS `b_awz_bxapi_handlers` (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `URL` varchar(1255) NOT NULL,
    `HASH` varchar(64) NOT NULL,
    `PARAMS` longtext NOT NULL,
    `PORTAL` varchar(65) NOT NULL,
    `DATE_ADD` datetime NOT NULL,
    PRIMARY KEY (`ID`),
    unique IX_HASH (HASH)
);