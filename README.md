# qscmf-clear-abandon
清理用户上传但已废弃的文件工具

```text
用户上传但已废弃的文件会占用大量的空间，可以使用此工具进行清理。

目前支持直接删除和软删除功能

线上项目建议使用软删除功能，观察一段时间，确保没有误删后再删除。
```

### 安装
```bash
composer require quansitech/qscmf-clear-abandon
```

### 修改配置
+ 执行命令
```bash
php artisan vendor:publish --provider="Provider: ClearAbandon\ClearAbandonServiceProvider"
```
  
+ 根据项目修改配置值，具体配置项查看配置文件 clearAbandon.php 注释

### 软删除
```text
将未使用的文件移动到临时目录，存放这些文件信息的数据备份至临时数据表。
```
#### 使用命令
+ 软删除
```bash
qscmf:clear-abandon --soft
```
或者
```bash
qscmf:clear-abandon --soft --type=soft
```

+ 恢复删除
```bash
qscmf:clear-abandon --soft --type=recover
```

+ 删除
```bash
qscmf:clear-abandon --soft --type=delete
```

##### 参数介绍
```text
type 可选值，默认为soft
soft: 将未使用的文件备份到临时目录，将对应数据表的数据存放到临时数据表
recover: 恢复至使用soft前的状态
delete: 将临时目录以及备份数据删除

```

```bash
# 选项可使用简写，如需要恢复删除操作
qscmf:clear-abandon -S -Trecover
```

### 直接删除
```text
删除未使用的文件以及存储这些文件信息的数据表数据
```

#### 使用命令
```bash
qscmf:clear-abandon
```
