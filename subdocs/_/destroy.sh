#!/usr/bin/env bash
# volumeを削除してdownする
docker-compose -p wf4 down -v
# 不要なコンテナ、イメージを一括削除する
docker system prune
# 不要なボリュームも一括削除する
docker volume prune


