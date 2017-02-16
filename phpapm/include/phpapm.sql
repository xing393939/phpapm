CREATE TABLE `phpapm_monitor` (
  `ID` bigint(15) unsigned NOT NULL AUTO_INCREMENT,
  `V1` varchar(100) DEFAULT NULL,
  `V2` varchar(100) DEFAULT NULL,
  `V3` varchar(200) DEFAULT NULL,
  `V4` text,
  `V5` varchar(200) DEFAULT NULL,
  `FUN_COUNT` int(11) unsigned DEFAULT '0',
  `CAL_DATE` datetime DEFAULT NULL,
  `V6` decimal(12,6) DEFAULT NULL COMMENT '最大耗时',
  `MD5` char(32) DEFAULT NULL COMMENT '唯一标志',
  `ADD_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TOTAL_DIFF_TIME` decimal(12,6) DEFAULT NULL COMMENT '花费总耗时',
  `MEMORY_MAX` decimal(12,6) DEFAULT NULL COMMENT '内存单次最大消耗',
  `MEMORY_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '内存消耗.总',
  `CPU_USER_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,单次最大',
  `CPU_USER_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,总',
  `CPU_SYS_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,单次最大',
  `CPU_SYS_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,总',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `MD5` (`MD5`),
  KEY `CAL_DATE` (`CAL_DATE`)
) ENGINE=InnoDB AUTO_INCREMENT=53375 DEFAULT CHARSET=utf8 COMMENT='所有监控';

CREATE TABLE `phpapm_monitor_config` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `V1` varchar(100) DEFAULT NULL,
  `V2` varchar(100) DEFAULT NULL,
  `ORDERBY` decimal(4,0) DEFAULT NULL,
  `AS_NAME` varchar(100) DEFAULT NULL COMMENT '页面上面显示的别名',
  `DAY_COUNT_TYPE` varchar(10) NOT NULL DEFAULT '0' COMMENT '日数据的统计方式:',
  `HOUR_COUNT_TYPE` varchar(10) NOT NULL DEFAULT '0' COMMENT '小时数据的统计方式:',
  `PERCENT_COUNT_TYPE` varchar(10) NOT NULL DEFAULT '0' COMMENT '是否显示百分比',
  `V2_GROUP` varchar(100) DEFAULT NULL COMMENT 'v2分组名称',
  `V2_CONFIG_OTHER` varchar(200) DEFAULT NULL COMMENT '序列化存储V2的其他属性,{NO_COUNT:true/false#标识数据是否需要统计入总数}',
  `COMPARE_GROUP` varchar(100) DEFAULT NULL,
  `VIRTUAL_COLUMNS` decimal(2,0) DEFAULT NULL,
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COMMENT='配置显示方式';

CREATE TABLE `phpapm_monitor_date` (
  `CAL_DATE` date NOT NULL,
  `V1` varchar(100) NOT NULL,
  `V2` varchar(100) NOT NULL,
  `FUN_COUNT` int(11) unsigned DEFAULT '0',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`CAL_DATE`,`V1`,`V2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日统计报表';

CREATE TABLE `phpapm_monitor_hour` (
  `CAL_DATE` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `V1` varchar(100) NOT NULL,
  `V2` varchar(100) NOT NULL,
  `V3` varchar(200) NOT NULL DEFAULT '',
  `FUN_COUNT` int(11) unsigned DEFAULT '0',
  `DIFF_TIME` decimal(12,6) DEFAULT NULL,
  `ADD_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TOTAL_DIFF_TIME` decimal(12,6) DEFAULT NULL COMMENT '花费总耗时',
  `MEMORY_MAX` decimal(12,6) DEFAULT NULL COMMENT '内存单次最大消耗',
  `MEMORY_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '内存消耗.总',
  `CPU_USER_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,单次最大',
  `CPU_USER_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,总',
  `CPU_SYS_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,单次最大',
  `CPU_SYS_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,总',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`CAL_DATE`,`V1`,`V2`,`V3`),
  UNIQUE KEY `V1` (`V1`,`V2`,`V3`,`CAL_DATE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `phpapm_monitor_queue` (
  `ID` bigint(15) unsigned NOT NULL AUTO_INCREMENT,
  `QUEUE` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1366 DEFAULT CHARSET=utf8;

CREATE TABLE `phpapm_monitor_v1` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `V1` varchar(100) NOT NULL COMMENT '名称',
  `AS_NAME` varchar(100) DEFAULT NULL COMMENT '页面上面显示的别名',
  `GROUP_NAME` varchar(100) NOT NULL DEFAULT '默认' COMMENT '分组名称',
  `START_CLOCK` decimal(2,0) NOT NULL DEFAULT '0' COMMENT '日数据默认开始小时时间',
  `DAY_COUNT_TYPE` varchar(10) DEFAULT NULL COMMENT '日数据的统计方式:',
  `HOUR_COUNT_TYPE` varchar(10) DEFAULT NULL COMMENT '小时数据的统计方式:',
  `PERCENT_COUNT_TYPE` varchar(10) DEFAULT NULL COMMENT '是否显示百分比',
  `GROUP_NAME_2` varchar(100) NOT NULL DEFAULT '默认',
  `GROUP_NAME_1` varchar(100) NOT NULL DEFAULT '业务分析',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='配置V1的各项基本信息';
