echo o > o.txt
cacls c:\temp\clone /T /C /G Administrateurs:F < o.txt
cacls c:\temp\clone /E /T /C /R Utilisateurs < o.txt
cacls c:\temp\clone /E /T /C /R "Tout le monde" < o.txt
