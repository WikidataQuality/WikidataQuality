bachelorarbeit
==============

### How To:

- TexLive installieren
- http://www.komascript.de/node/1786

###  In kommandozeile ausf�hren :
```
tlmgr repository add http://www.komascript.de/~mkohm/texlive-KOMA KOMA 
tlmgr pinning add KOMA koma-script
tlmgr install --reinstall koma-script
```

### TexMaker

- options -> editor -> 'check for external changes' aktivieren

### kompilieren

initial:

- pdflatex ausf�hren
- bibtex ausf�hren
- makeglossaries ausf�hren
- 2x pdflatex ausf�hren

