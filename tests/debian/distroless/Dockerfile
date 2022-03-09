FROM gcr.io/distroless/base-debian11

ARG TEST_BINARIES="/bin/bash /usr/bin/wget /usr/bin/curl /usr/local/bin/php"

ENV TEST_BINARIES ${TEST_BINARIES}

ADD "rootfs.tar" "/"

RUN /bin/sh -c " \
        set -eux && \
        for BINARY in ${TEST_BINARIES}; do \
            \${BINARY} --version; \
        done \
    "
