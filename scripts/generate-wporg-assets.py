#!/usr/bin/env python3
"""Generate WordPress.org icon/banner PNGs (no display name on the art)."""

from __future__ import annotations

import struct
import zlib
from pathlib import Path


def png(width: int, height: int, rgba_rows: list[bytes]) -> bytes:
    def chunk(tag: bytes, data: bytes) -> bytes:
        return struct.pack(">I", len(data)) + tag + data + struct.pack(">I", zlib.crc32(tag + data) & 0xFFFFFFFF)

    raw = b"".join(b"\x00" + row for row in rgba_rows)
    ihdr = struct.pack(">IIBBBBB", width, height, 8, 6, 0, 0, 0)
    return b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", ihdr) + chunk(b"IDAT", zlib.compress(raw, 9)) + chunk(b"IEND", b"")


def fill(w: int, h: int, color: tuple[int, int, int]) -> list[list[tuple[int, int, int, int]]]:
    r, g, b = color
    return [[(r, g, b, 255) for _ in range(w)] for _ in range(h)]


def rect(px, x1, y1, x2, y2, color, thickness=3):
    for t in range(thickness):
        for x in range(x1 + t, x2 - t + 1):
            if 0 <= y1 + t < len(px) and 0 <= x < len(px[0]):
                px[y1 + t][x] = (*color, 255)
            if 0 <= y2 - t < len(px) and 0 <= x < len(px[0]):
                px[y2 - t][x] = (*color, 255)
        for y in range(y1 + t, y2 - t + 1):
            if 0 <= y < len(px) and 0 <= x1 + t < len(px[0]):
                px[y][x1 + t] = (*color, 255)
            if 0 <= y < len(px) and 0 <= x2 - t < len(px[0]):
                px[y][x2 - t] = (*color, 255)


def hline(px, x1, x2, y, color, thickness=3):
    for t in range(thickness):
        yy = y + t - thickness // 2
        if 0 <= yy < len(px):
            for x in range(x1, x2 + 1):
                if 0 <= x < len(px[0]):
                    px[yy][x] = (*color, 255)


def disc(px, cx, cy, r, color):
    rr = r * r
    for y in range(cy - r, cy + r + 1):
        for x in range(cx - r, cx + r + 1):
            if 0 <= y < len(px) and 0 <= x < len(px[0]):
                if (x - cx) * (x - cx) + (y - cy) * (y - cy) <= rr:
                    px[y][x] = (*color, 255)


def fill_rect(px, x1, y1, x2, y2, color):
    for y in range(y1, y2 + 1):
        if 0 <= y < len(px):
            for x in range(x1, x2 + 1):
                if 0 <= x < len(px[0]):
                    px[y][x] = (*color, 255)


def rows(px) -> list[bytes]:
    return [b"".join(bytes(p) for p in row) for row in px]


BG = (11, 61, 74)
GOLD = (240, 180, 41)
LINE = (232, 244, 247)
DIM = (18, 82, 96)


def icon(size: int) -> bytes:
    px = fill(size, size, BG)
    pad = round(size * 0.18)
    box = size - 2 * pad
    x1, y1 = pad, pad
    x2, y2 = pad + box, pad + box
    thick = max(2, size // 32)
    rect(px, x1, y1, x2, y2, LINE, thick)
    ly = round(y1 + box * 0.28)
    gap = round(box * 0.18)
    lx1 = x1 + round(box * 0.14)
    lx2 = x2 - round(box * 0.14)
    lthick = max(2, size // 28)
    for i in range(3):
        hline(px, lx1, lx2, ly + i * gap, GOLD if i == 0 else LINE, lthick)
    spark = round(size * 0.09)
    disc(px, x2 - spark, y1 + spark, spark, GOLD)
    return png(size, size, rows(px))


def banner(w: int, h: int) -> bytes:
    px = fill(w, h, BG)
    fill_rect(px, 0, 0, round(w * 0.38), h - 1, DIM)
    pad = round(h * 0.22)
    boxw = round(w * 0.22)
    x1 = round(w * 0.08)
    y1 = pad
    x2 = x1 + boxw
    y2 = h - pad
    thick = max(3, h // 40)
    rect(px, x1, y1, x2, y2, LINE, thick)
    ly = y1 + round((y2 - y1) * 0.28)
    gap = round((y2 - y1) * 0.18)
    lx1 = x1 + round(boxw * 0.14)
    lx2 = x2 - round(boxw * 0.14)
    lthick = max(3, h // 28)
    for i in range(3):
        hline(px, lx1, lx2, ly + i * gap, GOLD if i == 0 else LINE, lthick)
    spark = round(h * 0.08)
    disc(px, x2 - spark, y1 + spark, spark, GOLD)
    return png(w, h, rows(px))


def main() -> None:
    out = Path(__file__).resolve().parents[1] / ".wordpress-org"
    out.mkdir(parents=True, exist_ok=True)
    files = {
        "icon-128x128.png": icon(128),
        "icon-256x256.png": icon(256),
        "banner-772x250.png": banner(772, 250),
        "banner-1544x500.png": banner(1544, 500),
    }
    for name, data in files.items():
        path = out / name
        path.write_bytes(data)
        print(path)


if __name__ == "__main__":
    main()
