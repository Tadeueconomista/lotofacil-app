import socket
import subprocess
import time
import sys
import os

def testar_conexao(host, port):
    try:
        with socket.create_connection((host, port), timeout=2):
            print(f"✅ Conexão bem-sucedida com {host}:{port}")
            return True
    except Exception as e:
        print(f"⏳ Tentativa falhou: {e}")
        return False

print("🔍 Python usado:", sys.executable)

# Testa se o backend já está rodando
if not testar_conexao("127.0.0.1", 5000):
    print("🔄 Backend não encontrado. Tentando iniciar...")

    # Caminho absoluto do Python
    python_path = "C:\\Python313\\python.exe"

    # Caminho do diretório onde está o app.py
    backend_dir = "C:\\xampp\\htdocs\\SorteioApp"

    # Caminho completo do script
    script_path = os.path.join(backend_dir, "app.py")

    # Inicia o backend
    subprocess.Popen([python_path, script_path], cwd=backend_dir)

    # Aguarda até 10 segundos, testando a cada 2 segundos
    for i in range(5):
        time.sleep(2)
        print(f"⏳ Verificando tentativa {i+1}...")
        if testar_conexao("127.0.0.1", 5000):
            print("✅ Backend iniciado com sucesso.")
            exit(0)

    print("❌ Backend não respondeu após tentativa de inicialização.")
    exit(1)
else:
    print("✅ Backend já estava ativo.")
    exit(0)