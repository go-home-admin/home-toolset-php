# home-toolset-php
使用php编写的go编译工具

为什么是php而不是go, 快速验证思路, 想在写伪代码无法运行时也能正常生成依赖提示；

而不是正常编写完整程序, 能正常启动程序后才使用反射去生成；

# 依赖工具
~~~~shell
php toolset make:bean ./../home-admin
~~~~
检查目录`./../home-admin`下的所有文件, 符合以下代码的注释`@Bean`和`inject:""`生成自动依赖的文件`z_inject_gen.go`
~~~~go
// @Bean
type kernel struct {
    httpServer *services.HttpServer `inject:""`
}
// New{YourStruct}Provider() 函数被定制的话, 不会在z_inject_gen.go重复生成
// New{YourStruct}Provider() 服务提供函数, 如果有业务可以自己编写, 不依赖工具
~~~~
`z_inject_gen.go`内容
~~~~go
// 代码由home-admin生成, 不需要编辑它

package http

import (
    services "github.com/cyz-home/home-admin/bootstrap/services"
)

var kernelSingle *kernel

func NewkernelProvider(httpServer *services.HttpServer) *kernel {
    kernel := &kernel{}
    kernel.httpServer = httpServer
    return kernel
}

func InitializeNewkernelProvider() *kernel {
	if kernelSingle == nil {
		kernelSingle = NewkernelProvider(
			services.InitializeNewHttpServerProvider(),
		)
	}

	return kernelSingle
}
~~~~

`NewkernelProvider` 如无必要应该只有 `InitializeNewkernelProvider` 调用。
而`InitializeNewkernelProvider`才是其他业务或者测试逻辑初始函数, 当然作为一个整体系统开发来说,
`InitializeNewkernelProvider` 也是由框架层的引导程序初始化, 一般也很少调用, 单元测试用的多。

# proto自定义标签
~~~~
// proto文件顶部注释, 全部struct标签后都统一加上bson标签
// @Tag("bson") 和 @Tag("bson", "{name}") 等价

// 属性标签
message ApiDemoHomeRequest {
  // @Tag("gorm", "primaryKey")
  int64 id = 1;
}
~~~~

# 系统地使用

写一个能够半低代码是go框架
https://{gomodule}
