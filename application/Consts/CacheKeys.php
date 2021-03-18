<?php


namespace app\Consts;

/**
 *
 * 键值命名规范
 * 1. 键值对统一大写;
 * 2. 值末位记得加上 ':' ;
 * 3. 值名采用下划线 '_' 来分段，第一段尽量以Model名来命名
 * 4. 常量命名规则按照以下方式 模块名_功能_其他标识
 * PS: 补充说明，redis中':'作为键命名空间分隔符，所以键定义最好用 [ 模块名:功能:其他标识 ] 这种来定义。redis key统一放到这里是为了防止键冲突
 */
class CacheKeys
{
	const DEVELOPER_TOKEN_SIGN = 'DEVELOP:TOKEN_SIGN:';
	const DATA_QUEUE = 'DATA_QUEUE:';
}