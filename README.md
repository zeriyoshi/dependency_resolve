# dependency_resolve - distroless packaging support

Binary packaging support tool for distroless / alpine.

## Usage

```shell
$ dependency_resolve "$(which ldd)" /usr/local/bin/php /bin/bash >> dependencies.txt
$ tar cfPpv "rootfs.tar" --files-from="dependencies.txt"
```

See test.sh and Dockerfile for details.
