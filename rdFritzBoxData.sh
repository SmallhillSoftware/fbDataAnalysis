#!/bin/bash

USER='whoami'

source "../mysql_creds.conf"

VAL_DATE=$(date +%Y-%m-%d_%H:%M:%S)
VAL_DATE=$(echo $VAL_DATE | sed 's/_/ /')
FB_UP_TIME=$(./upnpquery.sh urn:schemas-upnp-org:service:WANIPConnection:1 /igdupnp/control/WANIPConn1 GetStatusInfo NewUptime)
FB_IP_ADDR=$(./upnpquery.sh urn:schemas-upnp-org:service:WANIPConnection:1 /igdupnp/control/WANIPConn1 GetExternalIPAddress NewExternalIPAddress)
FB_TOTAL_BYTES_SENT=$(./upnpquery.sh urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1 /igdupnp/control/WANCommonIFC1 GetTotalBytesSent NewTotalBytesSent)
FB_TOTAL_BYTES_RCVD=$(./upnpquery.sh urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1 /igdupnp/control/WANCommonIFC1 GetTotalBytesReceived NewTotalBytesReceived)
FB_UPSTREAM_BITRATE=$(./upnpquery.sh urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1 /igdupnp/control/WANCommonIFC1 GetCommonLinkProperties NewLayer1UpstreamMaxBitRate)
FB_DOWNSTREAM_BITRATE=$(./upnpquery.sh urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1 /igdupnp/control/WANCommonIFC1 GetCommonLinkProperties NewLayer1DownstreamMaxBitRate)
FB_BYTE_SEND_RATE=$(./upnpquery.sh urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1 /igdupnp/control/WANCommonIFC1 GetAddonInfos NewByteSendRate)
FB_BYTE_RCV_RATE=$(./upnpquery.sh urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1 /igdupnp/control/WANCommonIFC1 GetAddonInfos NewByteReceiveRate)
#echo "date/time           : $VAL_DATE"
#echo "uptime              : $FB_UP_TIME"
#echo "ip-addr             : $FB_IP_ADDR"
#echo "total bytes sent    : $FB_TOTAL_BYTES_SENT"
#echo "total bytes received: $FB_TOTAL_BYTES_RCVD"
#echo "upstream bit rate   : $FB_UPSTREAM_BITRATE"
#echo "downstream bit rate : $FB_DOWNSTREAM_BITRATE"
#echo "byte sent rate      : $FB_BYTE_SEND_RATE"
#echo "byte receive rate   : $FB_BYTE_RCV_RATE"

MYSQL_CMD=$(echo "INSERT INTO \`dataVals\` (\`valDate\`, \`valUptime\`, \`valBytesSent\`, \`valBytesReceived\`, \`valUpstreamBitRate\`, \`valDownstreamBitRate\`) VALUES ('$VAL_DATE', '$FB_UP_TIME', '$FB_TOTAL_BYTES_SENT', '$FB_TOTAL_BYTES_RCVD', '$FB_UPSTREAM_BITRATE', '$FB_DOWNSTREAM_BITRATE')")

#echo $MYSQL_CMD

mysql --database=fb_conn_data --user=$USER --password=$PSWD<<EOF
$MYSQL_CMD
EOF




