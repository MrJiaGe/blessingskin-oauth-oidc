# 声明插件冲突

> 来源：https://bs-plugin.netlify.app/guide/conflicts.html
>
> 💡 此特性需要 5.0.0 版本或以上。

有时插件不希望与某些其它插件同时运行（例如官方「你好，多莉」插件不能与「一言」插件同时运行，因为都在页面同一位置显示）。

通过 `package.json` 中 `enchants.conflicts` 字段声明：

```json
{
  "enchants": {
    "conflicts": {
      "a": "*",
      "b": "^3.0.0"
    }
  }
}
```

`conflicts` 是键值对结构：

- 键：其它插件的 `name`
- 值：冲突的版本范围（支持语义化版本）

上例中：

- 当前插件不能与任何版本的 `a` 插件同时运行
- 不能与 `3.x` 版本的 `b` 插件同时运行（允许 `2.x` 或 `4.x`）