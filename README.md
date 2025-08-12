# 团队费用管理系统

一个基于 Laravel + Filament 的团队费用管理系统，用于管理团队日常费用、汇率、固定费用等数据。

## 🚀 功能特性

### 基础档案管理
- **团队管理**：团队信息维护，支持启用/停用状态
- **每日汇率**：支持多币种汇率管理（USD、CNY、PKR、INR）
- **固定费用**：月度固定费用管理
- **每日IP费用**：服务器和IP费用记录

### 业务数据管理
- **团队每日数据**：团队日常运营数据统计
  - 固定费用自动计算
  - 浮动人员费用
  - 服务器与IP费用
  - 推广广告费用
  - 消息发送统计
  - 在线人数统计
  - 自动计算增长率和毛利

### 权限管理
- **角色控制**：支持 viewer（只读）、admin（完全权限）等角色
- **功能限制**：viewer 角色只能查看数据，无法进行写操作

## 🛠️ 技术栈

- **后端框架**：Laravel 10.x
- **管理面板**：Filament 3.x
- **数据库**：MySQL 8.0+
- **前端**：Alpine.js + Tailwind CSS
- **权限管理**：Spatie Laravel Permission

## 📋 系统要求

- PHP >= 8.1
- Composer
- MySQL >= 8.0

## 🚀 安装步骤

### 1. 克隆项目
```bash
git clone https://github.com/Luckybob666/laravel-admin.git
cd laravel-admin
```

### 2. 安装依赖
```bash
composer install
```

### 3. 环境配置
```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件，配置数据库连接：
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. 创建必要目录并设置权限
```bash
# 创建必要的目录（确保目录存在）
mkdir -p bootstrap/cache
mkdir -p storage/framework/{cache,sessions,views}

# 设置存储目录权限
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 设置目录所有者（Linux/Unix系统）
# 对于宝塔面板环境，通常使用 www 用户
chown -R www:www bootstrap/cache storage

# 对于其他Linux环境，通常使用 www-data 用户
# sudo chown -R www-data:www-data storage
# sudo chown -R www-data:www-data bootstrap/cache
```

### 5. 数据库迁移和种子数据
```bash
php artisan migrate
php artisan db:seed
```

### 6. 生成应用缓存（可选，但推荐）
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. 启动开发服务器
```bash
php artisan serve
```

访问 `http://localhost/admin` 进入管理后台。

## 👥 默认用户

系统会自动创建以下默认用户：

- **管理员**：admin@example.com / password
- **查看者**：viewer@example.com / password

## 📊 数据模型

### 核心实体
- **Team**：团队信息
- **ExchangeRate**：每日汇率
- **FixedExpense**：固定费用
- **DailyIpCost**：每日IP费用
- **TeamDailyStat**：团队每日统计

### 业务逻辑
- 固定费用按团队数量自动分摊
- 汇率支持多币种转换
- 团队数据唯一性约束（每个团队每天只能有一条记录）

## 🔐 权限说明

### Viewer 角色
- ✅ 查看所有数据
- ❌ 无法创建、编辑、删除数据
- ❌ 无法使用批量操作

### Admin 角色
- ✅ 完全的数据管理权限
- ✅ 可以使用所有功能

## 🚀 部署

### 重要说明：缓存文件
项目中的 `bootstrap/cache` 目录被 `.gitignore` 排除是正常的设计：
- 这些缓存文件会在应用运行时自动生成
- 不会影响程序的正常安装和运行
- 在生产环境中，建议运行缓存命令以提高性能

### 生产环境配置
```bash
# 设置生产环境
APP_ENV=production
APP_DEBUG=false

# 创建必要的目录（确保目录存在）
mkdir -p bootstrap/cache
mkdir -p storage/framework/{cache,sessions,views}

# 设置目录权限（重要！）
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 设置目录所有者
# 对于宝塔面板环境
chown -R www:www bootstrap/cache storage

# 对于其他Linux环境
# chown -R www-data:www-data storage
# chown -R www-data:www-data bootstrap/cache

# 优化配置
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 服务器部署注意事项

#### 宝塔面板环境
如果使用宝塔面板，请特别注意：
```bash
# 1. 确保目录存在
mkdir -p bootstrap/cache
mkdir -p storage/framework/{cache,sessions,views}

# 2. 设置权限（宝塔面板通常使用 www 用户）
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www:www bootstrap/cache storage

# 3. 如果遇到权限问题，可以尝试更宽松的权限
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

#### 常见问题排查
- **500错误**：检查目录权限和所有者设置
- **缓存问题**：清除缓存 `php artisan cache:clear`
- **日志权限**：确保 `storage/logs` 目录可写

### 定时任务
建议设置以下定时任务：
```bash
# 每天凌晨1点确保汇率数据
0 1 * * * cd /path/to/your/project && php artisan ensure:daily-exchange-rates
```

## 🤝 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开 Pull Request

## 📝 更新日志

### v1.0.0
- 初始版本发布
- 基础档案管理功能
- 团队每日数据统计
- 权限管理系统

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情。

## 🆘 支持

如果你遇到问题或有建议，请：
1. 查看 [Issues](../../issues) 页面
2. 创建新的 Issue 描述问题
3. 联系项目维护者

---

**注意**：请确保在生产环境中修改默认密码和敏感配置信息。
