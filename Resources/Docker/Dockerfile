FROM ubuntu:trusty
MAINTAINER ingo@typo3.org

ENV TIKA_VERSION 1.15

# install dependencies
# Java 8 is not available for trusty/14.04
RUN apt-get update && apt-get install -y openjdk-7-jre-headless wget

# install Apache Tika
# todo automatically pick closest mirror
# todo check signature
RUN wget "http://apache.osuosl.org/tika/tika-server-${TIKA_VERSION}.jar" -O "/tika-server-${TIKA_VERSION}.jar"

EXPOSE 9998
ENTRYPOINT java -jar /tika-server-${TIKA_VERSION}.jar -h 0.0.0.0
