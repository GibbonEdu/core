FROM python:3.11-slim

WORKDIR /app

# 使用国内 PyPI 镜像
RUN pip config set global.index-url https://mirrors.aliyun.com/pypi/simple/

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
