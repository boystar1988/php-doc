# {{$ajaxTitle}}
##### 功能模块:{{$ajaxName}}
##### 作者:[{{$author}}](#)
##### 时间:[{{$date}}](#)

------------

{{block-method}}
#### [{{$methodTitle}}]
##### （1）路由:
    {{$methodRoute}}
##### （2）请求方式：
    {{$method}}
##### （3）请求参数：
|   参数名 |   类型  |   描述  |
|---------|--------|---------|
{{block-param}}
|{{$paramName}}|{{$paramType}}|{{$paramComment}}|
{{endBlock-param}}
##### （4）返回数据：（JSON）
```
{{$methodReturn}}
```
{{endBlock-method}}