-- phpMyAdmin SQL Dump
-- version 3.4.8
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2014 年 08 月 27 日 09:54
-- 服务器版本: 5.1.63
-- PHP 版本: 5.2.17p1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `dxslaw`
--

-- --------------------------------------------------------

--
-- 表的结构 `phpapm_monitor`
--

CREATE TABLE IF NOT EXISTS `phpapm_monitor` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `V1` varchar(100) CHARACTER SET gb2312 DEFAULT NULL,
  `V2` varchar(100) CHARACTER SET gb2312 DEFAULT NULL,
  `V3` varchar(200) CHARACTER SET gb2312 DEFAULT NULL,
  `V4` text CHARACTER SET gb2312,
  `V5` varchar(200) CHARACTER SET gb2312 DEFAULT NULL,
  `FUN_COUNT` int(11) unsigned DEFAULT NULL,
  `CAL_DATE` datetime DEFAULT NULL,
  `V6` decimal(12,6) DEFAULT NULL,
  `MD5` char(32) CHARACTER SET gb2312 DEFAULT NULL COMMENT '唯一标志',
  `ADD_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TOTAL_DIFF_TIME` decimal(18,2) DEFAULT NULL COMMENT '花费总耗时',
  `MEMORY_MAX` decimal(12,6) DEFAULT NULL COMMENT '内存单次最大消耗',
  `MEMORY_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '内存消耗.总',
  `CPU_USER_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,单次最大',
  `CPU_USER_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,总',
  `CPU_SYS_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,单次最大',
  `CPU_SYS_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,总',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `MD5` (`MD5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='所有监控' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `phpapm_monitor_config`
--

CREATE TABLE IF NOT EXISTS `phpapm_monitor_config` (
  `V1` varchar(100) CHARACTER SET gb2312 DEFAULT NULL,
  `V2` varchar(100) CHARACTER SET gb2312 DEFAULT NULL,
  `COUNT_TYPE` varchar(10) CHARACTER SET gb2312 DEFAULT NULL COMMENT '日综合数据的统计方式',
  `V3_LINK` varchar(200) CHARACTER SET gb2312 DEFAULT NULL COMMENT 'V3的连接方式',
  `V4_LINK` varchar(200) CHARACTER SET gb2312 DEFAULT NULL COMMENT 'V4的连接方式',
  `ORDERBY` decimal(4,0) DEFAULT NULL,
  `PHONE` varchar(100) CHARACTER SET gb2312 DEFAULT NULL COMMENT '手机号码,分号隔开',
  `PHONE_ORDER` decimal(10,0) DEFAULT NULL COMMENT '手机通知条件大于',
  `PHONE_ORDER_LESS` decimal(10,0) DEFAULT NULL COMMENT '手机通知条件小于',
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `AS_NAME` varchar(100) CHARACTER SET gb2312 DEFAULT NULL COMMENT '页面上面显示的别名',
  `DAY_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 NOT NULL DEFAULT '0' COMMENT '日数据的统计方式:',
  `HOUR_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 NOT NULL DEFAULT '0' COMMENT '小时数据的统计方式:',
  `MIN_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 NOT NULL DEFAULT '0' COMMENT '分钟数据的统计方式:',
  `PERCENT_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 NOT NULL DEFAULT '0' COMMENT '是否显示百分比',
  `V2_GROUP` varchar(100) CHARACTER SET gb2312 DEFAULT NULL COMMENT 'v2分组名称',
  `V2_COMPARE` decimal(4,0) DEFAULT '0' COMMENT '0为不显示，1为显示',
  `PINFEN_RULE` text CHARACTER SET gb2312 COMMENT '评分规则',
  `V2_CONFIG_OTHER` varchar(200) CHARACTER SET gb2312 DEFAULT NULL COMMENT '序列化存储V2的其他属性,{NO_COUNT:true/false#标识数据是否需要统计入总数}',
  `COMPARE_GROUP` varchar(100) CHARACTER SET gb2312 DEFAULT NULL,
  `VIRTUAL_COLUMNS` decimal(2,0) DEFAULT NULL,
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配置显示方式' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `phpapm_monitor_date`
--

CREATE TABLE IF NOT EXISTS `phpapm_monitor_date` (
  `CAL_DATE` date NOT NULL,
  `V1` varchar(100) CHARACTER SET gb2312 NOT NULL,
  `V2` varchar(100) CHARACTER SET gb2312 NOT NULL,
  `FUN_COUNT` decimal(18,2) DEFAULT NULL,
  `LOOKUP` datetime DEFAULT NULL COMMENT '验收过数据',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`CAL_DATE`,`V1`,`V2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日统计报表';

-- --------------------------------------------------------

--
-- 表的结构 `phpapm_monitor_hour`
--

CREATE TABLE IF NOT EXISTS `phpapm_monitor_hour` (
  `CAL_DATE` datetime DEFAULT NULL,
  `V1` varchar(100) CHARACTER SET gb2312 NOT NULL,
  `V2` varchar(100) CHARACTER SET gb2312 NOT NULL,
  `V3` varchar(200) CHARACTER SET gb2312 DEFAULT NULL,
  `FUN_COUNT` float(18,2) DEFAULT NULL,
  `DIFF_TIME` decimal(12,6) DEFAULT NULL,
  `ADD_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TOTAL_DIFF_TIME` decimal(18,2) DEFAULT NULL COMMENT '花费总耗时',
  `MEMORY_MAX` decimal(12,6) DEFAULT NULL COMMENT '内存单次最大消耗',
  `MEMORY_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '内存消耗.总',
  `CPU_USER_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,单次最大',
  `CPU_USER_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '用户消耗CPU,总',
  `CPU_SYS_TIME_MAX` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,单次最大',
  `CPU_SYS_TIME_TOTAL` decimal(12,6) DEFAULT NULL COMMENT '系统消耗CPU,总',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  UNIQUE KEY `V1` (`V1`,`V2`,`V3`,`CAL_DATE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `phpapm_monitor_v1`
--

CREATE TABLE IF NOT EXISTS `phpapm_monitor_v1` (
  `V1` varchar(100) CHARACTER SET gb2312 NOT NULL COMMENT '名称',
  `AS_NAME` varchar(100) CHARACTER SET gb2312 DEFAULT NULL COMMENT '页面上面显示的别名',
  `COUNT_TYPE` varchar(10) CHARACTER SET gb2312 DEFAULT NULL COMMENT '日综合数据的统计方式',
  `CHAR_TYPE` decimal(10,0) DEFAULT NULL COMMENT '默认显示的图标类型',
  `GROUP_NAME` varchar(100) CHARACTER SET gb2312 NOT NULL DEFAULT '默认' COMMENT '分组名称',
  `START_CLOCK` decimal(2,0) NOT NULL DEFAULT '0' COMMENT '日数据默认开始小时时间',
  `SHOW_TEMPLATE` decimal(2,0) NOT NULL DEFAULT '0' COMMENT '采用的显示模板',
  `SHOW_ALL` decimal(2,0) NOT NULL DEFAULT '1' COMMENT '显示汇总',
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `DAY_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 DEFAULT NULL COMMENT '日数据的统计方式:',
  `HOUR_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 DEFAULT NULL COMMENT '小时数据的统计方式:',
  `MIN_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 DEFAULT NULL COMMENT '分钟数据的统计方式:',
  `PERCENT_COUNT_TYPE` varchar(10) CHARACTER SET gb2312 DEFAULT NULL COMMENT '是否显示百分比',
  `SHOW_AVG` decimal(2,0) NOT NULL DEFAULT '0' COMMENT '显示平均',
  `GROUP_NAME_2` varchar(100) CHARACTER SET gb2312 NOT NULL DEFAULT '默认',
  `GROUP_NAME_1` varchar(100) CHARACTER SET gb2312 NOT NULL DEFAULT '业务分析',
  `DUIBI_NAME` text CHARACTER SET gb2312 COMMENT '对比分组名',
  `IS_DUTY` decimal(2,0) NOT NULL DEFAULT '0' COMMENT '是否需要验收',
  `PINFEN_RULE` text CHARACTER SET gb2312 COMMENT '评分规则',
  `PINFEN_RULE_NAME` varchar(40) CHARACTER SET gb2312 DEFAULT NULL COMMENT '顶部菜单显示的评分标准名称',
  `OCI_UNIQUE` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模拟ocirowcount',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配置V1的各项基本信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `phpapm_monitor_queue`
--

CREATE TABLE IF NOT EXISTS `phpapm_monitor_queue` (
  `id` bigint(15) unsigned NOT NULL AUTO_INCREMENT,
  `queue` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
