echo o > o.txt
cacls c:\temp /E /T /G "Tout le monde":R < o.txt
cacls c:\temp /E /T /G "Tout le monde":W < o.txt
cacls c:\temp /E /T /G "Tout le monde":C < o.txt
