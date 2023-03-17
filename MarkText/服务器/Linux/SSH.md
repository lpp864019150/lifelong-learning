# SSH

#### 0、参考文档

* [Linux系列 | SSH 如何使用密钥登录服务器](https://cloud.tencent.com/developer/article/1780788)
* [【CSDN】ssh之 ~/.ssh/config 配置文件实现](https://blog.csdn.net/weixin_45697293/article/details/125119796)
* [Git 配置多个 SSH-Key](https://cloud.tencent.com/developer/article/1555258)

#### 1、支持ssh key的方式登录服务器

1. 在客户端生成`ssh key`对，生成文件后，设置 `chmod 600` 防止其他用户读取
   
   ```
   # ssh-keygen -t rsa -b 4096 -C "your_email@domain.com" -f ~/.ssh/example_id_rsa
   # chmod 600 ~/.ssh/example_id_rsa
   # chmod 600 ~/.ssh/example_id_rsa.pub
   ```

2. 把`~/.ssh/example_id_rsa.pub`公钥上传到服务端，在服务端对应用户下面编辑文件 `~/.ssh/authorized_keys`把该公钥添加进去，也即以后使用该key登录时会以该用户身份登录服务器
   
   ```shell
   # 每个key单独一行，注意这里是 .pub 公钥
   ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDNeQGJiozawsDrpprfyCuu9mt5yAbspmf2Hc2CA5nbKfPe++CtXgkn9iuH19dFfWQ8H
   rufPEPgc2h2SkHVT7dLZGFd7H4kGAQJ3J+hRbBdSJW0l8ea7djGzN7w//Nv3yWnrCSzg5wCSvQjWhPjDVtYYPTvLnG2FMVqCKc5FH5CUH
   VCLFuIQusxYn3HGFuNfXDMN2zHxjL0PY0KaOWyx1yTKBlenETFruAvs1HnYQDm+EY1Eu3WEXO/lct0qquoPXj+k7I8Aa7Y9gTBJpEqpPC
   ozAl+Iuir5vE9/4cNGJnFiXr5SbsShPovcMiJZVdeBgSOQS4x6z9pt3GHq+qtceZAYlWVbyL9bekdsSxC9AY5Zdw6c8YkXemqfUycybE0
   ZOLa4oATACMKzPJbXcuYmuIuC5CSEzkHSPQEvXFC7hKQVrsvXHDSOujS8Hp+iM2r8h/N12/zr9SXyTkUeZFS4en8+tw9+Bu+Kd+rROqMe
   aDtlVswOjou6NcwDppWufk7A3b2W94uqjUrcopMfSFqmDSrETmzrHmze8TT3vJJW88vdHus9Rg/5NdgJ1p+RW1b3M+LIsyVft0ezfwYKb
   V5a+g8htzCFQ1/thnsr3x4T4d7QGAe+99vPFjxRJB0aiw35SE4pLyUv+HMU1pwd+oWEs1LAUFTcHpXMboFawT6raYVNw== your_email
   @domain.com
   ```

3. 在用户侧，可以使用私钥的方式进行登录了

4. 也可以在用户侧的 `~/.ssh/config` 里做一个配置，后续直接 `ssh Host` 就可以连接上服务器
   
   ```shell
   Host example
   HostName 127.0.0.1
   Port 22
   IdentityFile   ~/.ssh/example_id_rsa
   ```
   
   ```shell
   ssh example # 可以直接登录上面配置Host为example的机子
   ```

5. 去掉密码登录，只需要密钥登录的方式，在服务器编辑文件 `/etc/ssh/sshd_config`
   
   ```shell
   # 设置不可使用密码登录，密码登录不安全
   PasswordAuthentication no
   ```

6. 

#### 2、支持多git账号对应不同ssh key

编辑家目录下的`ssh`配置文件 `~/.ssh/confg`

```shell
# vim ~/.ssh/config

# gitee
Host gitee.com
HostName gitee.com
PreferredAuthentications publickey
IdentityFile ~/.ssh/gitee_id_rsa
# github
Host github.com
HostName github.com
PreferredAuthentications publickey
IdentityFile ~/.ssh/github_id_rsa
```
