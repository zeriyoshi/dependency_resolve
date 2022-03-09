#!/bin/sh

SCRIPT_DIR="$(cd "$(dirname "${0}")" && pwd)"

INSTALL_PACKAGES_DEBIAN="wget"
PACKAGING_BINARIES_DEBIAN="/bin/sh /bin/bash /usr/bin/wget /usr/bin/curl /usr/local/bin/php"

INSTALL_PACKAGES_ALPINE="wget bash"
PACKAGING_BINARIES_ALPINE="${PACKAGING_BINARIES_DEBIAN}"

TEST_BINARIES="/bin/bash /usr/bin/wget /usr/bin/curl /usr/local/bin/php"

set -eux && \
cd "${SCRIPT_DIR}/tests/debian" && \
    cd "$(pwd)/builder" && \
        docker build \
            -t"drslv_debian_builder" \
            -f"Dockerfile" \
            --build-arg INSTALL_PACKAGES="${INSTALL_PACKAGES_DEBIAN}" \
            --build-arg PACKAGING_BINARIES="${PACKAGING_BINARIES_DEBIAN}" \
            "${SCRIPT_DIR}" && \
    cd - && \
    cd "$(pwd)/distroless" && \
        docker run --rm -i "drslv_debian_builder" cat /rootfs.tar > "rootfs.tar" && \
        docker build \
            -t"drslv_debian_distroless" \
            -f"Dockerfile" \
            --build-arg TEST_BINARIES="${TEST_BINARIES}" \
            "$(pwd)" && \
        rm "rootfs.tar" && \
cd - && \
cd "${SCRIPT_DIR}/tests/alpine" && \
    cd "$(pwd)/builder" && \
        docker build \
            -t"drslv_alpine_builder" \
            -f"Dockerfile" \
            --build-arg INSTALL_PACKAGES="${INSTALL_PACKAGES_ALPINE}" \
            --build-arg PACKAGING_BINARIES="${PACKAGING_BINARIES_ALPINE}" \
            "${SCRIPT_DIR}" && \
    cd - && \
    cd "$(pwd)/base" && \
        docker run --rm -i "drslv_alpine_builder" cat /rootfs.tar > "rootfs.tar" && \
        docker build \
            -t"drslv_alpine_base" \
            -f"Dockerfile" \
            --build-arg TEST_BINARIES="${TEST_BINARIES}" \
            "$(pwd)" && \
        rm "rootfs.tar"
