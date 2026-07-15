from pathlib import Path

root = Path(r"C:\vibe\hat1\back\src\Command\Data")
long = []
for p in root.rglob("level_*.php"):
    for line in p.read_text(encoding="utf-8").splitlines():
        line = line.strip().rstrip(",")
        if len(line) >= 2 and line[0] == "'" and line[-1] == "'":
            s = line[1:-1].replace("\\'", "'")
            if len(s) > 100:
                long.append((len(s), p.as_posix(), s))

print("count", len(long))
for a, b, c in sorted(long, reverse=True)[:25]:
    print(a, b.split("Data/")[-1], c)
