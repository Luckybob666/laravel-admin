# 团队每日数据自动计算功能

## 功能概述

团队每日数据中的 `var_server_ip_cost`（服务器/IP费用）字段现在支持自动计算，不再需要手动填写。系统会根据您提供的算法自动计算每个团队的费用分摊。

## 计算算法

### 基础参数
1. **当日有效团队数** = 参与分摊的团队数量（启用中的团队）
2. **当日单个团队固定费用** = 当月固定费用总额 ÷ 当月天数 ÷ 当日有效团队数
3. **当日单个团队 IP 费用** = 当日公司级 IP 费用 ÷ 当日有效团队数
4. **当日单个团队总费用** = 当日单个团队固定费用 + 当日单个团队 IP 费用

### 按消息量分摊
5. **全公司当日消息总数** = 所有团队当日消息数之和
6. **当前团队当日费用占比** = 当前团队当日消息数 ÷ 全公司当日消息总数
7. **全公司当日总费用** = （当月固定费用总额 ÷ 当月天数）+ 当日公司级 IP 费用
8. **当前团队当日分摊费用** = 全公司当日总费用 × 当前团队当日费用占比

## 数据依赖

### 必需数据
- **固定费用**：在 `FixedExpense` 表中按月设置
- **IP费用**：在 `DailyIpCost` 表中按日设置
- **团队信息**：在 `Team` 表中设置，需要 `is_active` 字段标识启用状态
- **消息数量**：在 `TeamDailyStat` 表中填写每个团队的消息条数

### 数据优先级
1. 如果当日没有消息，按团队数量平均分摊
2. 如果有消息，按消息量比例分摊

## 使用方法

### 1. 表单操作
- 选择日期和团队后，`fixed_cost` 和 `var_server_ip_cost` 会自动计算
- 修改消息数量后，`var_server_ip_cost` 会重新计算
- 字段显示为禁用状态，但会保存到数据库

### 2. 批量重新计算
使用命令行工具重新计算历史数据：

```bash
# 重新计算最近7天（默认）
php artisan team-daily-stats:recalculate

# 重新计算指定日期
php artisan team-daily-stats:recalculate --date=2024-01-15

# 重新计算日期范围
php artisan team-daily-stats:recalculate --start-date=2024-01-01 --end-date=2024-01-31

# 重新计算所有数据
php artisan team-daily-stats:recalculate --all

# 仅显示将要进行的操作，不实际执行
php artisan team-daily-stats:recalculate --dry-run
```

## 自动更新机制

### 观察者模式
系统使用观察者模式自动处理数据变更：

1. **TeamDailyStatObserver**：当团队每日数据变更时，重新计算同一天所有团队的费用
2. **DailyIpCostObserver**：当IP费用变更时，重新计算该日期所有团队的费用

### 触发条件
- 创建新的团队每日数据
- 更新团队每日数据（特别是消息数量）
- 删除团队每日数据
- 创建/更新/删除IP费用记录

## 性能优化

### 计算优化
- 使用 `saveQuietly()` 避免观察者循环
- 批量处理减少数据库查询
- 缓存计算结果避免重复计算

### 数据库优化
- 建议在相关字段上建立索引：
  ```sql
  CREATE INDEX idx_team_daily_stats_date ON team_daily_stats(date);
  CREATE INDEX idx_team_daily_stats_team_date ON team_daily_stats(team_id, date);
  CREATE INDEX idx_daily_ip_costs_date ON daily_ip_costs(date);
  ```

## 注意事项

### 数据一致性
- 确保每个日期都有对应的IP费用记录
- 确保每月都有对应的固定费用记录
- 确保团队状态正确（`is_active` 字段）

### 计算精度
- 所有金额保留2位小数
- 使用 `round()` 函数避免浮点数精度问题

### 错误处理
- 如果缺少必要数据，相关字段会显示为0
- 系统会记录计算过程中的异常情况

## 扩展功能

### 添加新字段
如果需要添加新的计算字段，可以：

1. 在 `TeamDailyStatCalculationService` 中添加新的计算方法
2. 在表单中添加相应的字段和联动逻辑
3. 在观察者中添加重新计算逻辑

### 自定义算法
可以通过修改 `TeamDailyStatCalculationService` 中的 `computeServerIpCost` 方法来调整计算算法。

## 故障排除

### 常见问题
1. **费用显示为0**：检查是否有对应的固定费用和IP费用记录
2. **计算不准确**：检查团队状态和消息数量是否正确
3. **性能问题**：考虑添加数据库索引或使用缓存

### 调试命令
```bash
# 查看计算过程
php artisan team-daily-stats:recalculate --dry-run --date=2024-01-15

# 检查数据完整性
php artisan tinker
>>> App\Models\TeamDailyStat::whereDate('date', '2024-01-15')->get(['team_id', 'msg_count', 'fixed_cost', 'var_server_ip_cost']);
```
