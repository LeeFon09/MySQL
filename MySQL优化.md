#数据库性能调优
数据库调优，就好比盖楼打地基，地基打得不稳，楼层一高，就会塌方。数据库也是如此，数据少，并发小，隐藏的问题是发现不了的，只要达到一定规模后，所有的问题就会全部曝露出来了，所以前期的设计阶段尤为重要。

## 数据库优化分类
* 硬件
* 网络
* 软件

>硬件、网络取决于公司的经济实力。

>软件再分为表设计(字段类型、存储引擎)、SQL语句优化与索引、配置文件参数、体系架构等方面的优化。

### 表设计优化
> 一个好的数据库设计对于数据库的性能优化常常会起到事半功倍的效果。合理的数据库结构不仅可以使数据库占用更小的磁盘空间，而且能够使查询速度更快。

#### 表优化的常用方法
* 将字段很多的表分解成多个表
> 对于字段较多的表，如果有些字段的使用频率很低，可以将这些字段分离出来形成新表。因为当一个表的数据量很大的时候，会由于使用频率低的字段的存在而变慢。

* 增加冗余字段，适度冗余

> 设计数据库表时应尽量遵守范式理论的约定，尽可能减少冗余字段。但是合理地加入冗余字段也可以提高查询速度。这就是以空间换时间。

总结：在开发应用程序时，设计的数据库要最大程度地遵守三范式。但是，三范式最大的问题在于通常需要join很多表，而这个会导致查询效率很低。<span style="color:red">所以有时候基于查询性能考虑，</span>我们需要有意违反三范式，适度的冗余，以达到提高查询效率的目的。


### 字段类型的选取
> 原则：选择字段的一般原则是保小不保大，能用占用字节少的字段就不用大字段。 

* 数字类型
	
![](./img/2016-08-05_201403.png)

tinyint类型最大存储是255。
	
	create table tmp(id tinyint);
	
	insert into tmp(id) values(256);//溢出

* int(11) vs int(21)
存储空间还是存储范围有区别？
	int(11)与int(21)的存储空间与存储范围是一样的。

区别是：如果你选择是int(11)，那么你存放了一个1，那么结果是1前面有10个0，int(21)前面有20个零

	实验：
		create table t(a int(11) zerofill,b int(21) zerofill);//zerofill 是补全零的

		insert into t values(1,1);

		select * from t;

		+-------------+-----------------------+
		| a           | b                     |
		+-------------+-----------------------+
		| 00000000001 | 000000000000000000001 |
		+-------------+-----------------------+
		1 row in set (0.00 sec)


字符串类型
* char

	char存储空间定长，容易造成空间的浪费。char数据类型存储大小最大为255字符。

	最大255个字符的意思是最大只能存放255个字母或者255个汉字
	
* varchar

	varchar存储变长，节省存储空间,varchar需要一位来存储长度。varchar是使用多少，就使用多少空间。所以通常都是选择varchar。

	varchar数据类型可以存储<span style="color:red">超过255个字符</span>

	注意：char和varchar存储单位为字符。字符与字节需要换算。


	实验：
			//char最大长度255字符，所以报错
			mysql> create table c(a char(256));

		ERROR 1074 (42000): Column length too big for column 'a' (max = 255); 
		use BLOB or TEXT instead

		//varchar存储长度可以超过255
		create table c(a varchar(256));
		Query OK, 0 rows affected (0.16 sec)

* 字符与字节的关系

	如果是utf8字符集，因为utf8存放中文占用三个字节大小，所以存放两个中文需要6个字节大小。

	一个英语字母无论什么情况下都是占用一个字节的，所以varchar(6)就可以存放github这个英语单词了


### 日期 
* date

	date三个字节，如2015-05-01只能存储到天数。date精确到年月日
* time

	time三个字节,只能存小时分钟，time精确到小时分钟秒
* datetime

	datetime八字节，可以存储年月日时分秒
* timestamp

	timestamp四字节，可以存储年月日时分秒。

####  字符串类型总结
* char与varchar定义的长度是字符长度不是字节长度
* 存储字符串推荐选择使用varchar(n),n尽量小

		


## 如何选择表引擎
> InnoDB支持行锁、事务。如果应用中需要执行大量的读写操作，应该选择InnoDB，这样可以提高多用户并发操作的性能。在MySQL5.5之后版本，Oracle已经很少支持MyISAM了，所以建议优先选择InnoDB引擎。



## SQL优化与合理利用索引
系统优化中一个很重要的方面就是SQL语句的优化。对于海量数据，劣质SQL语句和高效SQL语句之间的速度差别可以达到上百倍。

### 如何定位执行很慢的SQL语句
<span style="color:red">开启慢查询日志的好处是可以通过记录、分析慢SQL语句来优化SQL语句</span>

开启慢查询日志，在my.cnf配置文件中，加入以下参数：

	slow_query_log = 1
	slow_query_log_file = mysql.slow
	long_query_time = 1   	 # 超过1秒的SQL会记录下来

### SQL语句优化建议
* 避免使用子查询，可以用left join表连接替换
* limit分页优化
	
	传统的分页：select SQL_NO_CACHE * from t2 order by id limit 99999,10;
	
	传统的的分页，虽然用上了id索引，但要从第一行开始起定位到99999行，然后再扫描出后10行，相当于进行一个全表扫描，显然效率不高。

	优化方法：

	select SQL_NO_CACHE * from t2 where id >= 100000 order by id limit 10;

	优化方法利用id索引直接定位100000行，然后再扫描出后10行。速度相当快。
	
* 避免使用\*号，只查需要的字段
* 多使用limit，减少数据传输

	
		用户查看所有的数据。  ajax异步请求。

* 可以使用冗余来减少关联表查询
* 给常在where条件后的字段添加索引，并且合理使用索引

## 索引
分类：主键索引、唯一索引、普通索引、全文索引

### 合理使用索引
适当的索引对应用的性能来说相当重要，而且也建议在MySQL中使用索引，它的速度是很快的。

但是索引也是有成本的。<span style="color:red">每次向表中写入时，如果带有一个或多个索引，那么MySQL也要更新各个索引。索引还增加了数据库的规模，也就是说索引也是占据空间的。</span>

只有当某列被用于where子句时，才能享受索引性能提升的好处。如果不使用索引，它就没有价值，而且会带来维护上的开销。


### 索引常见用法
* 依据where查询条件建立索引

		select a,b from tab a where c = ? //应该给c建立索引
* 使用联合索引，而不是多个单列索引

		select * from tab where a = ? and b =?
		//给(a,b)建立联合索引，而不是分别给a,b建立索引
		alter table tab add index in_ab(a,b);

* 联合索引中索引字段的顺序根据区分度排，区分度大的放在前面
	
		//(name,sex);//将name放前面，因为name的区分度更大。因为sex只有0 1 2 这个三个值。
			  

* 合理创建联合索引，避免冗余
	
		//(a),(a,b),(a,b,c)只要给(a,b,c)建立索引就行
* order by 、group by 、 distrinct字段添加索引

#### 用不到索引的情况(避免)
* 字段使用函数，将不能用到索引

		select createtime from aa where date(createtime) = curdate();
	
		//where后面的字段(createtime)使用函数,将不会使用到索引。

* 用数字当字符类型，数字一定要加引号

		select * from user where name = 123 //这SQL语句用不到name索引
		
		select * from user where name = '123' //这样写才会用到name字段上的索引

* 在使用like关键字进行查询的语句中，如果匹配字符串的第一个字符为"%"，索引不会起作用。
		
			//用不到索引的		
		 desc select *  from t where name like "%j%"\G;

		//用到索引
		 desc select *  from t where name like "j%"\G;

		
* 联合索引，只有查询条件中使用了这些字段中第一个字段时，索引才会被使用

		create index in_name on user(name,email)
		explain select * from user where name = 'jack'; //用到索引
		explain select * from user where email = 'jack@qq.com'; //用不到索引



* 使用OR关键字的查询语句

	* 查询语句的查询条件中只有OR关键字，且OR前后的两个条件中的列都是索引时，查询中才使用索引。否则查询将不使用索引。


* order by 的字段混合使用asc和desc用不到索引

		select * from user order by id desc,name asc;

* where 子句使用的字段和order by 的字段不一致

		select * from user where name = 'jack' order by id;

* 对不同关键字使用order by 排序

		select * from user order by name,id;






	

	

