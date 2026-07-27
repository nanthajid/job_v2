# table structure

TABLE `employee` (
  `EmpID` varchar(15) NOT NULL,
  `EmpName` varchar(100) DEFAULT NULL,
  `SexNo` int(1) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `KNo` int(1) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `SDate` datetime DEFAULT NULL,
  PRIMARY KEY (`EmpID`)
)

TABLE `register` (
  `DocNo` int(11) NOT NULL AUTO_INCREMENT,
  `DocID` varchar(16) DEFAULT NULL,
  `RDate` varchar(15) DEFAULT NULL,
  `SDate` datetime DEFAULT NULL,
  `QNo` int(1) DEFAULT NULL,
  `KNo` int(2) DEFAULT NULL,
  `SexNo` int(1) DEFAULT NULL,
  `EmpID` varchar(15) DEFAULT NULL,
  `StID` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`DocNo`),
  UNIQUE KEY `register_index` (`DocNo`)
)

TABLE `selft_rep` (
  `DocNo` int(11) NOT NULL AUTO_INCREMENT,
  `DocID` varchar(16) DEFAULT NULL,
  `RDate` varchar(15) DEFAULT NULL,
  `SDate` datetime DEFAULT NULL,
  `QNo` int(1) DEFAULT NULL,
  `JNo` int(1) DEFAULT NULL,
  `KNo` int(2) DEFAULT NULL,
  `SexNo` int(1) DEFAULT NULL,
  `EmpID` varchar(15) DEFAULT NULL,
  `StID` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`DocNo`),
  UNIQUE KEY `register_index` (`DocNo`)
)

TABLE `job` (
  `JNo` int(11) NOT NULL,
  `JName` varchar(255) NOT NULL,
  PRIMARY KEY (`JNo`)
)

TABLE `quit` (
  `QNo` int(1) NOT NULL,
  `QName` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`QNo`)
)

TABLE `kate` (
  `KNo` int(3) NOT NULL,
  `KName` varchar(50) DEFAULT '',
  PRIMARY KEY (`KNo`)
)

TABLE `users` (
  `DocNo` int(11) NOT NULL AUTO_INCREMENT,
  `UserName` varchar(250) NOT NULL,
  `Password` varchar(250) NOT NULL,
  `level` char(1) DEFAULT NULL,
  `StID` varchar(15) NOT NULL,
  PRIMARY KEY (`DocNo`)
) 

TABLE `levels` (
  `levelNo` int(11) NOT NULL AUTO_INCREMENT,
  `levelName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`levelNo`)
)

TABLE `staff` (
  `StID` varchar(15) NOT NULL,
  `StName` varchar(100) DEFAULT NULL,
  `SexNo` char(1) DEFAULT NULL,
  `TitleNo` char(1) DEFAULT NULL,
  `StPost` char(2) DEFAULT '',
  `DepNo` char(2) DEFAULT NULL,
  `image` text DEFAULT NULL,
  PRIMARY KEY (`StID`)
)

TABLE `titles` (
  `TitleNo` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(15) DEFAULT '',
  PRIMARY KEY (`TitleNo`)
)

TABLE `position` (
  `StPost` char(255) NOT NULL,
  `StPostName` varchar(150) DEFAULT NULL,
  `PostType` char(2) DEFAULT NULL,
  PRIMARY KEY (`StPost`)
) 

TABLE `department` (
  `DepNo` char(2) NOT NULL,
  `DepName` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`DepNo`)
)

TABLE `post_type` (
  `PostType` char(255) NOT NULL,
  `PostTypeName` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`PostType`)
)

TABLE `educational_qualification` (
  `EqNo` int(11) NOT NULL AUTO_INCREMENT,
  `EqName` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`EqNo`)
)
TABLE `emp_position` (
  `PotNo` int(11) NOT NULL AUTO_INCREMENT,
  `PotName` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`PotNo`)
)