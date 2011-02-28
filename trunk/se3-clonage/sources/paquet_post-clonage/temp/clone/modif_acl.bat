echo o > o.txt
cacls c:\temp\clone /E /T /C /R Utilisateurs < o.txt
cacls c:\temp\clone /E /T /C /R "Tout le monde" < o.txt
cacls c:\temp\clone /T /C /G Administrateurs:R < o.txt
cacls c:\temp\clone /T /C /G Administrateurs:W < o.txt
cacls c:\temp\clone /T /C /G Administrateurs:C < o.txt
cacls c:\temp\clone /T /C /G Administrateurs:F < o.txt
