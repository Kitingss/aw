"""
KAI WAR BOT v5
Jalankan: python kai_war_bot.py
Butuh   : pip install requests
"""

import requests, time, os, sys, random
from datetime import datetime

API_URL = "https://sc-microservice-tiketkai.bmsecure.id/train/search"

USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Edg/122.0.0.0",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36",
]

R="\033[0m";B="\033[1m";CY="\033[96m";GR="\033[92m"
RD="\033[91m";YL="\033[93m";GY="\033[90m";WH="\033[97m"

def cls(): os.system("cls" if os.name=="nt" else "clear")

def beep():
    try:
        import winsound
        for _ in range(3): winsound.Beep(1000,300); time.sleep(0.1)
    except: sys.stdout.write("\a\a\a"); sys.stdout.flush()

def buka_browser(asal, tujuan, tanggal, dewasa):
    import webbrowser
    webbrowser.open(f"https://m.tiketkai.com/jadwal-kereta?org={asal}&des={tujuan}&tgl={tanggal}&adult={dewasa}")

def input_setup():
    cls()
    print(f"{CY}{B}{'═'*56}{R}")
    print(f"{CY}{B}  🚂  KAI WAR BOT v5{R}")
    print(f"{CY}{'═'*56}{R}\n")

    asal   = input(f"  {WH}Stasiun asal    {GY}(contoh: PSENSN){R} : {WH}").strip().upper(); print(R,end="")
    tujuan = input(f"  {WH}Stasiun tujuan  {GY}(contoh: PK){R}     : {WH}").strip().upper(); print(R,end="")

    while True:
        tgl = input(f"  {WH}Tanggal         {GY}(YYYY-MM-DD){R}     : {WH}").strip(); print(R,end="")
        try: datetime.strptime(tgl,"%Y-%m-%d"); break
        except: print(f"  {RD}Format salah! Contoh: 2026-03-20{R}")

    while True:
        try:
            dws = int(input(f"  {WH}Jumlah penumpang{GY} (angka){R}         : {WH}").strip()); print(R,end="")
            if dws>=1: break
        except: pass
        print(f"  {RD}Masukkan angka valid!{R}")

    while True:
        try:
            itv = int(input(f"  {WH}Interval cek    {GY}(detik, rekomendasi 3){R}: {WH}").strip() or "3"); print(R,end="")
            if 1<=itv<=30: break
        except: pass
        print(f"  {RD}Masukkan angka 1-30!{R}")

    # Instruksi ambil payload
    print(f"\n{CY}{'─'*56}{R}")
    print(f"  {YL}{B}LANGKAH WAJIB — Ambil payload dari browser:{R}")
    print(f"""
  1. Buka {WH}m.tiketkai.com/jadwal-kereta{R} di Chrome
  2. Cari rute {WH}{asal} → {tujuan}{R} tanggal {WH}{tgl}{R}
  3. Tekan {WH}F12{R} → tab {WH}Network{R} → filter {WH}Fetch/XHR{R}
  4. Klik request {WH}'search'{R}
  5. Klik kanan → {WH}Copy → Copy as cURL (bash){R}
  6. Paste di bawah (ambil bagian {WH}--data-raw '...'{R} saja)
""")
    print(f"{CY}{'─'*56}{R}")
    print(f"  {GY}Paste payload (string panjang base64), lalu Enter:{R}")
    print(f"  {WH}", end="")

    payload = input().strip()
    print(R, end="")

    # Bersihkan kalau user paste seluruh cURL
    if "--data-raw" in payload:
        try:
            payload = payload.split("--data-raw '")[1].rstrip("'")
        except:
            pass
    payload = payload.strip("'\"")

    if len(payload) < 50:
        print(f"  {RD}Payload terlalu pendek, sepertinya salah. Coba lagi.{R}")
        sys.exit(1)

    print(f"\n  {GR}✔ Payload diterima ({len(payload)} karakter){R}")
    print(f"  {GY}Rute: {WH}{asal} → {tujuan}{R}  {GY}Tanggal: {WH}{tgl}{R}  {GY}Interval: {WH}{itv}s{R}")
    print(f"\n  {YL}Enter untuk mulai...{R}"); input()

    return asal, tujuan, tgl, dws, itv, payload

def run(asal, tujuan, tanggal, dewasa, interval, payload):
    tick=0; total_found=0; total_err=0; rl_count=0
    found_trains={}; alerted=set(); cooldown=0

    while True:
        if cooldown > 0:
            cls()
            print(f"{CY}{B}{'═'*56}{R}")
            print(f"  {YL}⚠  Rate limited! Cooldown {cooldown}s lagi...{R}")
            print(f"  {GY}Rate-limit total: {YL}{rl_count}{R}   Tiket: {GR}{total_found}{R}")
            print(f"{CY}{'─'*56}{R}")
            time.sleep(1); cooldown -= 1
            continue

        tick += 1
        now = datetime.now().strftime("%H:%M:%S")
        error_msg = None; trains_raw = []

        headers = {
            "accept": "application/json, text/plain, */*",
            "accept-language": "en-US,en;q=0.9,id;q=0.8",
            "content-type": "text/plain",
            "origin": "https://m.tiketkai.com",
            "referer": "https://m.tiketkai.com/",
            "user-agent": random.choice(USER_AGENTS),
            "sec-ch-ua": '"Chromium";v="146", "Not-A.Brand";v="24", "Google Chrome";v="146"',
            "sec-ch-ua-mobile": "?0",
            "sec-ch-ua-platform": '"Windows"',
            "sec-fetch-dest": "empty",
            "sec-fetch-mode": "cors",
            "sec-fetch-site": "cross-site",
        }

        try:
            r = requests.post(API_URL, data=payload, headers=headers, timeout=8)
            if r.status_code == 429:
                total_err += 1; rl_count += 1
                cooldown = min(10 + rl_count*3, 60)
                error_msg = f"Rate limited! Cooldown {cooldown}s"
            elif r.status_code != 200:
                total_err += 1; error_msg = f"HTTP {r.status_code}"
            else:
                rl_count = max(0, rl_count-1)
                trains_raw = r.json().get("data", [])
        except requests.Timeout:
            total_err += 1; error_msg = "Timeout"
        except Exception as e:
            total_err += 1; error_msg = str(e)[:60]

        found_trains.clear()
        for train in trains_raw:
            nama=train.get("trainName","").upper()
            jam=train.get("departureTime",""); tiba=train.get("arrivalTime","")
            for seat in train.get("seats",[]):
                kelas=seat.get("class",""); avail=seat.get("availability",0)
                harga=seat.get("priceAdult",0)
                if avail>0:
                    key=f"{nama}_{kelas}"
                    found_trains[key]={"nama":nama,"kelas":kelas,"jam":jam,"tiba":tiba,"sisa":avail,"harga":harga}

        for key,t in found_trains.items():
            if key not in alerted:
                alerted.add(key); total_found+=1

        if found_trains:
            beep()

        cls()
        print(f"{CY}{B}{'═'*56}{R}")
        print(f"{CY}{B}  🚂  KAI WAR BOT  —  Running{R}")
        print(f"{CY}{'═'*56}{R}")
        print(f"  {GY}Rute    :{R} {WH}{asal}{R} {GY}→{R} {WH}{tujuan}{R}")
        print(f"  {GY}Tanggal :{R} {WH}{tanggal}{R}   {GY}Penumpang:{R} {WH}{dewasa}{R}   {GY}Interval:{R} {WH}{interval}s{R}")
        print(f"{CY}{'─'*56}{R}")

        status = f"{GR}{B}ADA TIKET!{R}" if found_trains else f"{GY}Memantau...{R}"
        print(f"  {GY}Refresh #{B}{tick}{R}      {GY}Waktu: {WH}{now}{R}   {status}")
        print(f"  {GY}Tiket ditemukan : {GR}{B}{total_found}{R}   {GY}Error : {RD}{total_err}{R}   {GY}Rate-limit : {YL}{rl_count}{R}")

        if error_msg:
            print(f"\n  {YL}[!] {error_msg}{R}")

        print(f"{CY}{'─'*56}{R}")

        if found_trains:
            print(f"  {GR}{B}✔ TIKET TERSEDIA — SEGERA BELI!{R}")
            for t in found_trains.values():
                harga_txt=f"Rp{int(t['harga']):,}" if t['harga'] else "-"
                print(f"\n    {GR}{B}{t['nama']}{R}  {GY}[{WH}{t['kelas']}{GY}]{R}")
                print(f"    {GY}Jam   :{R} {WH}{t['jam']}{R} → {WH}{t['tiba']}{R}")
                print(f"    {GY}Sisa  :{R} {GR}{B}{t['sisa']} kursi{R}")
                print(f"    {GY}Harga :{R} {WH}{harga_txt}{R}")
        else:
            print(f"  {GY}Belum ada tiket kosong.\n{R}")
            seen=set()
            for train in trains_raw:
                n=train.get("trainName","").upper()
                if n and n not in seen:
                    seen.add(n)
                    av=sum(s.get("availability",0) for s in train.get("seats",[]))
                    dot=f"{GR}●{R}" if av>0 else f"{RD}●{R}"
                    print(f"    {dot} {WH}{n}{R}")

        print(f"{CY}{'─'*56}{R}")
        print(f"  {GY}Ctrl+C untuk berhenti{R}")
        time.sleep(interval + random.uniform(0, 0.5))

def main():
    try: import requests
    except ImportError: print("pip install requests"); sys.exit(1)
    try:
        asal,tujuan,tanggal,dewasa,interval,payload = input_setup()
        run(asal,tujuan,tanggal,dewasa,interval,payload)
    except KeyboardInterrupt:
        cls(); print(f"\n  {CY}Bot dihentikan.{R}\n"); sys.exit(0)

if __name__=="__main__": main()
