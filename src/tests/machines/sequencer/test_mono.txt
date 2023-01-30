machine:bass  ; Which machine name to play this pattern on
measures:0, 2 ; Which song measures to play this pattern on
params:hex    ; Use hex values for event data

<line number> [note|x [velocity [s|m <controller value>]]]

00 d#2 3c s 80 3f    ; play note d#2 at velocity 0x3c, set controller #0x80 to 0x3f
01 c2  32            ; play note c2 at velocity 0x32
02 x                 ; stop note
03                   ; empty line could be left out
04 c2  40
05        m 80 01    ; no note, increment controller #0x80 value by 1
06 x
07 c2  3c
08 a#1               ; legato mode, change pitch to a#1 without new note
09 c2  3c
10 x
11 c2  3c
12
13 x
14        m 80 FF s 81 00; no note, decrement controller #0x80 value by 1, set controller #0x81 value to 0
15 d#2 3c
16 f2  3c
17
18 d#2 3c
19 x
20 c2  3c
21 g1  3c
22 a#1 3c
23 c2  3c
24
25 x
26
27
28 d#2 3c
29
30 x
31
