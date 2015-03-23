﻿

CREATE TABLE IF NOT EXISTS `constraints_ready_for_migration` (

  `ID` int(11) NOT NULL,

  `pid` int(11) DEFAULT NULL,

  `constraint_name` varchar(255) DEFAULT NULL,

  `class` text,

  `constraint_status` varchar(255) DEFAULT NULL,

  `comment` text,

  `known_exception` text,

  `group_by` varchar(255) DEFAULT NULL,

  `item` text,

  `list` text,

  `mandatory` varchar(255) DEFAULT NULL,

  `maximum_date` varchar(255) DEFAULT NULL,

  `maximum_quantity` varchar(255) DEFAULT NULL,

  `minimum_date` varchar(255) DEFAULT NULL,

  `minimum_quantity` varchar(255) DEFAULT NULL,

  `namespace` varchar(255) DEFAULT NULL,

  `pattern` varchar(255) DEFAULT NULL,

  `property` varchar(255) DEFAULT NULL,

  `relation` varchar(255) DEFAULT NULL,

  `snak` varchar(255) DEFAULT NULL

)