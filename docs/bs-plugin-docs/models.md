# 数据与模型

> 来源：https://bs-plugin.netlify.app/guide/models.html

- [简介](#简介)
- [基本操作](#基本操作)
    - [获取模型实例](#获取模型实例)
    - [更新模型数据](#更新模型数据)
    - [添加模型](#添加模型)
- [User 模型](#user-模型)
- [Player 模型](#player-模型)
- [Texture 模型](#texture-模型)
- [模型与模型之间的联系](#模型与模型之间的联系)

## 简介

Blessing Skin 中的每个模型都与数据库的数据表有联系，每个模型实例相当于数据表中的一行。Blessing Skin 所有的三个模型都是 Laravel Eloquent ORM 模型，位于 `App\Models` 下：

- `User`：用户
- `Player`：角色
- `Texture`：材质

> 所有 Eloquent ORM 模型都继承 `Illuminate\Database\Eloquent\Model`。即使模型类上没有声明属性，仍可访问模型实例上的数据。

## 基本操作

### 获取模型实例

按主键查找：

```php
$user = User::find(1);  // 不存在返回 null
```

按条件查找：

```php
$users = User::where('score', '>', 50)->get();  // 返回集合
$users->isEmpty();                              // 判断空集合
```

比较操作符为 `=` 时可省略第二参数：

```php
$user = User::where('email', 'a@b.c')->first();
```

### 更新模型数据

```php
$user->email = 'a@b.c';
$user->save();
```

### 添加模型

```php
$user = new User();
$user->email = 'a@b.c';
$user->nickname = 'abc';
$user->save();
```

> 自增主键无需手动填写。

## User 模型

主键 `uid`（自增整数）。

| 字段 | 含义 |
|------|------|
| `uid` | 用户唯一标识，不可修改 |
| `email` | 邮箱，唯一但不能代替 `uid` |
| `nickname` | 昵称，可重复 |
| `score` | 积分，整数 |
| `avatar` | 头像材质 tid，默认 `0` |
| `password` | 已 hash 的密码 |
| `ip` | 注册时 IP |
| `permission` | 权限：`-1`/`BANNED` 封禁，`0`/`NORMAL` 正常，`1`/`ADMIN` 管理员，`2`/`SUPER_ADMIN` 超级管理员 |
| `last_sign_at` | 上次签到时间 |
| `register_at` | 注册时间 |
| `verified` | 邮箱是否已验证 |
| `verification_token` | 邮箱验证校对（v4 起已废弃） |
| `remember_token` | 「记住我」标识（v4 起） |

实例方法：

- `isAdmin(): bool` — 是否为管理员（超管也算）
- `verifyPassword(string $rawPassword): bool` — 验证密码
- `changePassword(string $password): bool` — 更改密码

## Player 模型

主键 `pid`（自增整数）。

| 字段 | 含义 |
|------|------|
| `pid` | 角色唯一标识 |
| `uid` | 拥有者的 uid |
| `name` | 角色名 |
| `tid_skin` | 皮肤材质 tid |
| `tid_cape` | 披风材质 tid |
| `last_modified` | 上次修改时间 |

## Texture 模型

主键 `tid`（自增整数）。

| 字段 | 含义 |
|------|------|
| `tid` | 材质唯一标识 |
| `name` | 材质名称 |
| `type` | 类型：`steve` / `alex` / `cape` |
| `hash` | 文件 hash |
| `size` | 文件大小（KB） |
| `uploader` | 上传者 uid |
| `public` | 是否公开（数据库存数字，取出时为 bool） |
| `upload_at` | 上传时间 |

实例方法：

- `setPrivacy(bool $public): bool` — 修改公开性

```php
$texture->setPrivacy(true);   // 设为公开
$texture->setPrivacy(false);  // 设为私有
```

## 模型与模型之间的联系

### 关联

用户与角色的关系是「一对多」：

```php
$players = $user->players;   // 某用户的全部角色
$owner = $player->user;      // 角色的拥有者
```

### 注意

通过属性获取会得到全部关联实例。如需条件筛选：

```php
$players = $user->players()->where('preference', 'slim');
```