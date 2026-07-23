FROM python:3.12-slim

RUN pip install --no-cache-dir "paramiko>=3,<4"

COPY server.py /server.py

EXPOSE 22
CMD ["python", "/server.py"]
