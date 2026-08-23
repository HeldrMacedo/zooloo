#!/usr/bin/env python3
"""
Helper script to search across:
1. Adianti 8.4 Tutor examples (/home/helder/Desenvolvimento/Adianti8.4/tutor)
2. Adianti 8.4 Framework core (/home/helder/Desenvolvimento/Adianti8.4/framework)
3. Adianti 7 Book PDF (/home/helder/Desenvolvimento/zooloo/adianti7pdf_compress.pdf)
"""

import sys
import os
import subprocess
import argparse
import re

TUTOR_PATH = "/home/helder/Desenvolvimento/Adianti8.4/tutor"
FRAMEWORK_PATH = "/home/helder/Desenvolvimento/Adianti8.4/framework"
PDF_PATH = "/home/helder/Desenvolvimento/zooloo/adianti7pdf_compress.pdf"
CACHE_DIR = "/home/helder/.gemini/antigravity/adianti_cache"

def ensure_pdf_extracted():
    os.makedirs(CACHE_DIR, exist_ok=True)
    txt_path = os.path.join(CACHE_DIR, "adianti7_book.txt")
    if not os.path.exists(txt_path) and os.path.exists(PDF_PATH):
        try:
            subprocess.run(
                ["pdftotext", "-layout", PDF_PATH, txt_path],
                stderr=subprocess.DEVNULL,
                check=True
            )
        except Exception as e:
            print(f"Error extracting PDF: {e}", file=sys.stderr)
    return txt_path

def search_tutor(query, max_results=15):
    print(f"\n=======================================================")
    print(f"📌 [ADIANTI 8.4 TUTOR EXAMPLES]: '{query}'")
    print(f"=======================================================")
    if not os.path.exists(TUTOR_PATH):
        print(f"Tutor directory not found at {TUTOR_PATH}")
        return

    control_path = os.path.join(TUTOR_PATH, "app", "control")
    try:
        res = subprocess.run(
            ["rg", "-i", "-n", "-C", "2", "--max-count", str(max_results), query, control_path],
            capture_output=True, text=True
        )
        if res.stdout.strip():
            print(res.stdout[:5000])
        else:
            print("No direct matches in tutor/app/control.")
    except Exception as e:
        print(f"Error running rg: {e}")

def search_framework(query, max_results=10):
    print(f"\n=======================================================")
    print(f"📌 [ADIANTI 8.4 FRAMEWORK CORE]: '{query}'")
    print(f"=======================================================")
    if not os.path.exists(FRAMEWORK_PATH):
        print(f"Framework directory not found at {FRAMEWORK_PATH}")
        return

    lib_path = os.path.join(FRAMEWORK_PATH, "lib", "adianti")
    try:
        res = subprocess.run(
            ["rg", "-i", "-n", "-C", "1", "--max-count", str(max_results), query, lib_path],
            capture_output=True, text=True
        )
        if res.stdout.strip():
            print(res.stdout[:4000])
        else:
            print("No direct matches in framework/lib/adianti.")
    except Exception as e:
        print(f"Error running rg: {e}")

def search_pdf(query, max_matches=8):
    print(f"\n=======================================================")
    print(f"📌 [ADIANTI 7 BOOK PDF]: '{query}'")
    print(f"=======================================================")
    txt_path = ensure_pdf_extracted()
    if not os.path.exists(txt_path):
        print(f"PDF text cache not found at {txt_path}")
        return

    with open(txt_path, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()

    pages = content.split('\x0c') # Form feed separates pages
    matches = 0
    pattern = re.compile(re.escape(query), re.IGNORECASE)

    for page_num, page_text in enumerate(pages, 1):
        if pattern.search(page_text):
            matches += 1
            print(f"\n📖 --- Página {page_num} ---")
            lines = [l.strip() for l in page_text.splitlines() if l.strip()]
            matched_lines = [l for l in lines if pattern.search(l)]
            for line in matched_lines[:6]:
                print(f"  • {line}")
            if matches >= max_matches:
                print(f"\n[Exibindo os primeiros {max_matches} trechos do livro. Use --page <N> para ler a página completa.]")
                break
    
    if matches == 0:
        print("Nenhuma ocorrência encontrada no livro do Adianti 7.")

def get_page(page_num):
    txt_path = ensure_pdf_extracted()
    with open(txt_path, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()
    pages = content.split('\x0c')
    if 1 <= page_num <= len(pages):
        print(f"\n📖 === [LIVRO ADIANTI 7 - PÁGINA {page_num} de {len(pages)}] ===")
        print(pages[page_num - 1].strip())
    else:
        print(f"Página {page_num} fora do intervalo (1 - {len(pages)})")

def list_tutor_categories():
    print(f"\n=======================================================")
    print(f"📂 [CATEGORIAS DE EXEMPLOS DO ADIANTI 8.4 TUTOR]")
    print(f"=======================================================")
    control_dir = os.path.join(TUTOR_PATH, "app", "control")
    if os.path.exists(control_dir):
        for root, dirs, files in os.walk(control_dir):
            rel_path = os.path.relpath(root, control_dir)
            php_files = [f for f in files if f.endswith('.php')]
            if php_files:
                print(f"📁 {rel_path}: {len(php_files)} controllers/exemplos")

def main():
    parser = argparse.ArgumentParser(description="Consulta Adianti 8.4 Tutor, Framework e Livro Adianti 7")
    parser.add_argument("query", nargs="?", help="Termo ou classe para pesquisar")
    parser.add_argument("--page", type=int, help="Exibir página específica do livro PDF")
    parser.add_argument("--list-tutor", action="store_true", help="Listar estrutura de pastas de exemplos do Tutor 8.4")
    parser.add_argument("--source", choices=["all", "tutor", "framework", "book"], default="all", help="Fonte para consulta")
    args = parser.parse_args()

    if args.list_tutor:
        list_tutor_categories()
        return

    if args.page:
        get_page(args.page)
        return

    if not args.query:
        parser.print_help()
        return

    if args.source in ["all", "tutor"]:
        search_tutor(args.query)
    if args.source in ["all", "framework"]:
        search_framework(args.query)
    if args.source in ["all", "book"]:
        search_pdf(args.query)

if __name__ == "__main__":
    main()
