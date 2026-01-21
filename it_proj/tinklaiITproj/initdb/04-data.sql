-- Test data with 100+ IT questions with randomized answer positions
-- Correct answers are shuffled to different positions (not always first)
-- Using INSERT IGNORE to avoid duplicates on multiple runs

USE aistis_jakutonis;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Programming Languages (Python)
INSERT IGNORE INTO klausimas (id, klausimas, atsakymai, tema, verte, atsakymas) VALUES
(UNHEX('11111111111111111111111111111111'), 'Kokia funkcija naudojama Python programavimo kalboje sąrašų elementams surūšiuoti?', 'arrange()\norder()\nsort()\norganize()\nrank()', 'Python', 3, 'sort()'),
(UNHEX('11111111111111111111111111111112'), 'Kaip Python sukurti tuščią žodyną (dictionary)?', '[]\n{}\n()\n<>\n""', 'Python', 2, '{}'),
(UNHEX('11111111111111111111111111111113'), 'Kokia Python komanda naudojama aplinkos kintamųjų nuskaitymui?', 'sys.env\nenv.get()\nos.environ\ngetenv()\nenvironment()', 'Python', 4, 'os.environ'),
(UNHEX('11111111111111111111111111111114'), 'Kaip Python kalboje sukurti klasę?', 'def ClassName:\nclass ClassName:\nfunction ClassName:\nstruct ClassName:\nobject ClassName:', 'Python', 3, 'class ClassName:'),
(UNHEX('11111111111111111111111111111115'), 'Kokia funkcija naudojama Python eilutės ilgiui gauti?', 'length()\nsize()\nlen()\ncount()\nstrlen()', 'Python', 2, 'len()'),
(UNHEX('11111111111111111111111111111116'), 'Kaip Python importuoti modulį?', 'include module\nrequire module\nimport module\nuse module\nload module', 'Python', 2, 'import module'),
(UNHEX('11111111111111111111111111111117'), 'Kokia Python funkcija skirta teksto spausdinimui?', 'echo()\nprint()\nwrite()\noutput()\ndisplay()', 'Python', 1, 'print()'),
(UNHEX('11111111111111111111111111111118'), 'Kaip Python sukurti sąrašą (list)?', '{}\n[]\n()\n<>\n""', 'Python', 1, '[]'),
(UNHEX('11111111111111111111111111111119'), 'Kokia Python funkcija skirta duomenų tipo nustatymui?', 'typeof()\ngettype()\ntype()\nvartype()\ndatatype()', 'Python', 3, 'type()'),
(UNHEX('1111111111111111111111111111111A'), 'Kaip Python konvertuoti tekstą į skaičių?', 'toInt()\nparseInt()\nint()\nconvert()\nnumber()', 'Python', 2, 'int()'),

-- Programming Languages (JavaScript)
(UNHEX('22222222222222222222222222222221'), 'Kokia funkcija naudojama JavaScript masyvo elementų filtravimui?', 'where()\nfilter()\nselect()\nfind()\nsearch()', 'JavaScript', 4, 'filter()'),
(UNHEX('22222222222222222222222222222222'), 'Kaip JavaScript deklaruoti kintamąjį?', 'var x = 5\nconst x = 5\nint x = 5\nVisi aukščiau pateikti\nlet x = 5', 'JavaScript', 3, 'Visi aukščiau pateikti'),
(UNHEX('22222222222222222222222222222223'), 'Kokia funkcija naudojama JavaScript masyvo elemento paieškai?', 'findOne()\nsearch()\nlocate()\nfind()\nget()', 'JavaScript', 3, 'find()'),
(UNHEX('22222222222222222222222222222224'), 'Kaip JavaScript sukurti funkciją?', 'func name() {}\nfunction name() {}\ndef name() {}\nmethod name() {}\nproc name() {}', 'JavaScript', 2, 'function name() {}'),
(UNHEX('22222222222222222222222222222225'), 'Kokia funkcija naudojama JavaScript laukimui (delay)?', 'wait()\nsleep()\nsetTimeout()\ndelay()\npause()', 'JavaScript', 3, 'setTimeout()'),
(UNHEX('22222222222222222222222222222226'), 'Kaip JavaScript priskirti įvykį (event)?', 'onEvent()\nbind()\naddEventListener()\nattach()\nregister()', 'JavaScript', 4, 'addEventListener()'),
(UNHEX('22222222222222222222222222222227'), 'Kokia JavaScript operatorius naudojamas griežtam lyginimui?', '==\neq()\n===\nequals()\nis()', 'JavaScript', 3, '==='),
(UNHEX('22222222222222222222222222222228'), 'Kaip JavaScript sukurti promise?', 'Promise.create()\npromise()\nmakePromise()\nnew Promise()\ncreatePromise()', 'JavaScript', 4, 'new Promise()'),
(UNHEX('22222222222222222222222222222229'), 'Kokia funkcija naudojama JavaScript JSON analizei?', 'parseJSON()\nJSON.decode()\nJSON.parse()\njsonParse()\nfromJSON()', 'JavaScript', 3, 'JSON.parse()'),
(UNHEX('2222222222222222222222222222222A'), 'Kaip JavaScript konvertuoti objektą į JSON?', 'toJSON()\nJSON.encode()\nJSON.stringify()\nstringify()\nserialize()', 'JavaScript', 3, 'JSON.stringify()'),

-- Programming Languages (Java)
(UNHEX('33333333333333333333333333333331'), 'Kokia Java klasė naudojama tekstui saugoti?', 'Text\nString\nCharArray\nStringBuffer\nChar', 'Java', 2, 'String'),
(UNHEX('33333333333333333333333333333332'), 'Kaip Java deklaruoti konstantą?', 'const int X = 5\nstatic int X = 5\nfinal int X = 5\nreadonly int X = 5\nimmutable int X = 5', 'Java', 3, 'final int X = 5'),
(UNHEX('33333333333333333333333333333333'), 'Kokia Java klasė naudojama sąrašui (list)?', 'List\nArray\nArrayList\nVector\nCollection', 'Java', 3, 'ArrayList'),
(UNHEX('33333333333333333333333333333334'), 'Kaip Java paveldėti klasę?', 'inherits\nextends\nderives\nimplements\nsuper', 'Java', 2, 'extends'),
(UNHEX('33333333333333333333333333333335'), 'Kokia Java žodis naudojamas interfeiso implementacijai?', 'interface\nextends\nimplements\nuses\nrealize', 'Java', 3, 'implements'),
(UNHEX('33333333333333333333333333333336'), 'Kaip Java sukurti gijas (threads)?', 'implements Runnable\nAbi aukščiau\nnew Thread()\nextends Thread\nthread.start()', 'Java', 4, 'Abi aukščiau'),
(UNHEX('33333333333333333333333333333337'), 'Kokia Java klasė naudojama failų skaitymui?', 'FileInput\nReadFile\nFileStream\nFileReader\nInputFile', 'Java', 4, 'FileReader'),
(UNHEX('33333333333333333333333333333338'), 'Kaip Java apdoroti išimtis (exceptions)?', 'try-except\nhandle-error\ntry-catch\ncatch-error\nerror-handle', 'Java', 3, 'try-catch'),
(UNHEX('33333333333333333333333333333339'), 'Kokia Java anotacija naudojama perrašyti metodą?', '@Overwrite\n@Replace\n@Override\n@Redefine\n@Modify', 'Java', 3, '@Override'),
(UNHEX('3333333333333333333333333333333A'), 'Kaip Java sukurti HashMap?', 'HashMap.create()\nnew Map()\nnew HashMap<>()\ncreateHashMap()\nHashMap()', 'Java', 3, 'new HashMap<>()'),

-- Databases (SQL)
(UNHEX('44444444444444444444444444444441'), 'Kokia SQL komanda naudojama duomenų paieškai?', 'FIND\nSELECT\nGET\nSEARCH\nQUERY', 'SQL', 2, 'SELECT'),
(UNHEX('44444444444444444444444444444442'), 'Kaip SQL pridėti naują įrašą?', 'ADD TO\nINSERT INTO\nCREATE IN\nAPPEND TO\nPUT INTO', 'SQL', 2, 'INSERT INTO'),
(UNHEX('44444444444444444444444444444443'), 'Kokia SQL komanda naudojama duomenų atnaujinimui?', 'MODIFY\nCHANGE\nUPDATE\nALTER\nEDIT', 'SQL', 2, 'UPDATE'),
(UNHEX('44444444444444444444444444444444'), 'Kaip SQL ištrinti įrašą?', 'REMOVE FROM\nDROP FROM\nDELETE FROM\nCLEAR FROM\nERASE FROM', 'SQL', 2, 'DELETE FROM'),
(UNHEX('44444444444444444444444444444445'), 'Kokia SQL komanda naudojama lentelės kūrimui?', 'MAKE TABLE\nNEW TABLE\nCREATE TABLE\nADD TABLE\nBUILD TABLE', 'SQL', 3, 'CREATE TABLE'),
(UNHEX('44444444444444444444444444444446'), 'Kaip SQL sujungti dvi lenteles?', 'MERGE\nJOIN\nCOMBINE\nUNITE\nLINK', 'SQL', 3, 'JOIN'),
(UNHEX('44444444444444444444444444444447'), 'Kokia SQL funkcija naudojama įrašų skaičiavimui?', 'NUM()\nTOTAL()\nCOUNT()\nSUM_ROWS()\nQUANTITY()', 'SQL', 3, 'COUNT()'),
(UNHEX('44444444444444444444444444444448'), 'Kaip SQL rūšiuoti rezultatus?', 'SORT BY\nORDER BY\nARRANGE BY\nORGANIZE BY\nRANK BY', 'SQL', 2, 'ORDER BY'),
(UNHEX('44444444444444444444444444444449'), 'Kokia SQL komanda naudojama dublikatų šalinimui?', 'UNIQUE\nDISTINCT\nNO_DUPLICATES\nSINGLE\nONE', 'SQL', 3, 'DISTINCT'),
(UNHEX('4444444444444444444444444444444A'), 'Kaip SQL grupuoti rezultatus?', 'CLUSTER BY\nCATEGORY BY\nGROUP BY\nCLASS BY\nSET BY', 'SQL', 3, 'GROUP BY'),

-- Web (HTML/CSS)
(UNHEX('55555555555555555555555555555551'), 'Kokia HTML žymė naudojama antraštei?', '<header>\n<h1>\n<title>\n<head>\n<heading>', 'HTML/CSS', 2, '<h1>'),
(UNHEX('55555555555555555555555555555552'), 'Kaip CSS nustatyti fono spalvą?', 'color\nbg-color\nbackground-color\nbackcolor\nfill', 'HTML/CSS', 2, 'background-color'),
(UNHEX('55555555555555555555555555555553'), 'Kokia HTML žymė naudojama nuorodai?', '<link>\n<href>\n<a>\n<url>\n<ref>', 'HTML/CSS', 2, '<a>'),
(UNHEX('55555555555555555555555555555554'), 'Kaip CSS centruoti tekstą?', 'align: center\ntext-align: center\ncenter: text\ntext-center\nalign-text: center', 'HTML/CSS', 2, 'text-align: center'),
(UNHEX('55555555555555555555555555555555'), 'Kokia HTML žymė naudojama paveikslėliui?', '<image>\n<pic>\n<img>\n<picture>\n<photo>', 'HTML/CSS', 2, '<img>'),
(UNHEX('55555555555555555555555555555556'), 'Kaip CSS nustatyti elemento plotį?', 'size\nw\nwidth\nwide\nbreadth', 'HTML/CSS', 2, 'width'),
(UNHEX('55555555555555555555555555555557'), 'Kokia HTML žymė naudojama lentelei?', '<grid>\n<tbl>\n<table>\n<data>\n<sheet>', 'HTML/CSS', 2, '<table>'),
(UNHEX('55555555555555555555555555555558'), 'Kaip CSS nustatyti teksto spalvą?', 'text-color\nfont-color\ncolor\ntextcolor\nfg-color', 'HTML/CSS', 2, 'color'),
(UNHEX('55555555555555555555555555555559'), 'Kokia HTML žymė naudojama sąrašui?', '<list>\n<ol>\nAbi <ul> ir <ol>\n<ul>\n<items>', 'HTML/CSS', 2, 'Abi <ul> ir <ol>'),
(UNHEX('5555555555555555555555555555555A'), 'Kaip CSS nustatyti šrifto dydį?', 'size\ntext-size\nfontsize\nfont-size\nfont', 'HTML/CSS', 2, 'font-size'),

-- Operating Systems (Linux)
(UNHEX('66666666666666666666666666666661'), 'Kokia Linux komanda naudojama failų sąrašui?', 'dir\nlist\nls\nshow\nfiles', 'Linux', 2, 'ls'),
(UNHEX('66666666666666666666666666666662'), 'Kaip Linux pakeisti direktoriją?', 'chdir\ngoto\ncd\nmove\ndir', 'Linux', 2, 'cd'),
(UNHEX('66666666666666666666666666666663'), 'Kokia Linux komanda naudojama failo kopijavimui?', 'copy\ncp\ndup\nclone\nreplicate', 'Linux', 2, 'cp'),
(UNHEX('66666666666666666666666666666664'), 'Kaip Linux perkelti failą?', 'move\ntransfer\nmv\nshift\nrelocate', 'Linux', 2, 'mv'),
(UNHEX('66666666666666666666666666666665'), 'Kokia Linux komanda naudojama failo trynimui?', 'delete\ndel\nrm\nerase\nremove', 'Linux', 2, 'rm'),
(UNHEX('66666666666666666666666666666666'), 'Kaip Linux sukurti direktoriją?', 'md\nmakedir\nmkdir\ncreate\nnewdir', 'Linux', 2, 'mkdir'),
(UNHEX('66666666666666666666666666666667'), 'Kokia Linux komanda naudojama procesų sąrašui?', 'processes\nlist\nps\ntask\nproc', 'Linux', 3, 'ps'),
(UNHEX('66666666666666666666666666666668'), 'Kaip Linux nutraukti procesą?', 'stop\nend\nkill\nterminate\nquit', 'Linux', 3, 'kill'),
(UNHEX('66666666666666666666666666666669'), 'Kokia Linux komanda naudojama teisių keitimui?', 'chperm\nperms\nchmod\nrights\naccess', 'Linux', 3, 'chmod'),
(UNHEX('6666666666666666666666666666666A'), 'Kaip Linux peržiūrėti failo turinį?', 'view\nshow\ncat\nread\ndisplay', 'Linux', 2, 'cat'),

-- Networking
(UNHEX('77777777777777777777777777777771'), 'Koks yra standartinis HTTP portas?', '443\n8080\n80\n3000\n8000', 'Networking', 3, '80'),
(UNHEX('77777777777777777777777777777772'), 'Koks yra standartinis HTTPS portas?', '80\n8443\n443\n8080\n3000', 'Networking', 3, '443'),
(UNHEX('77777777777777777777777777777773'), 'Kokia komanda naudojama serverio pasiekiamumo patikrinimui?', 'check\ntest\nping\nreach\nverify', 'Networking', 3, 'ping'),
(UNHEX('77777777777777777777777777777774'), 'Koks protokolas naudojamas el. pašto siuntimui?', 'POP3\nIMAP\nSMTP\nHTTP\nFTP', 'Networking', 4, 'SMTP'),
(UNHEX('77777777777777777777777777777775'), 'Koks protokolas naudojamas saugiam duomenų perdavimui?', 'HTTP\nFTP\nHTTPS\nTelnet\nSSH', 'Networking', 3, 'HTTPS'),
(UNHEX('77777777777777777777777777777776'), 'Kokia komanda naudojama tinklo konfigūracijai Linux?', 'netconfig\nnetwork\nifconfig\nipset\nnetsetup', 'Networking', 4, 'ifconfig'),
(UNHEX('77777777777777777777777777777777'), 'Koks yra standartinis SSH portas?', '23\n21\n22\n25\n80', 'Networking', 3, '22'),
(UNHEX('77777777777777777777777777777778'), 'Kokia komanda naudojama DNS užklausoms?', 'dns\nlookup\nnslookup\nquery\nresolve', 'Networking', 4, 'nslookup'),
(UNHEX('77777777777777777777777777777779'), 'Koks protokolas naudojamas failų perdavimui?', 'HTTP\nFTP\nSMTP\nPOP3\nSSH', 'Networking', 3, 'FTP'),
(UNHEX('7777777777777777777777777777777A'), 'Kokia komanda naudojama maršruto nustatymui?', 'trace\nroute\ntraceroute\npath\ntrack', 'Networking', 4, 'traceroute'),

-- Security
(UNHEX('88888888888888888888888888888881'), 'Kas yra SQL injection?', 'Duomenų bazės klaida\nProgramavimo klaida\nSaugumo pažeidžiamumas\nTinklo problema\nKodavimo būdas', 'Security', 4, 'Saugumo pažeidžiamumas'),
(UNHEX('88888888888888888888888888888882'), 'Kas yra XSS ataka?', 'X-Server Security\nExtra System Safety\nExternal Script Security\nCross-Site Scripting\nCross-System Scan', 'Security', 5, 'Cross-Site Scripting'),
(UNHEX('88888888888888888888888888888883'), 'Kokia hash funkcija rekomenduojama slaptažodžiams?', 'MD5\nSHA1\nbcrypt\nBase64\nCRC32', 'Security', 5, 'bcrypt'),
(UNHEX('88888888888888888888888888888884'), 'Kas yra CSRF ataka?', 'Client-Side Resource Failure\nCross-Server Response Failure\nCross-Site Request Forgery\nCrypto System Random Function\nClient Security Request Filter', 'Security', 5, 'Cross-Site Request Forgery'),
(UNHEX('88888888888888888888888888888885'), 'Kokia yra saugiausia autentifikacijos metodu?', 'Slaptažodis\nBiometrija\nMulti-factor authentication\nPIN kodas\nSlaptas klausimas', 'Security', 5, 'Multi-factor authentication'),
(UNHEX('88888888888888888888888888888886'), 'Kas yra HTTPS sertifikatas?', 'Servero raktas\nSSL/TLS sertifikatas\nSlaptažodžio failas\nTinklo konfigūracija\nDomeno registracija', 'Security', 4, 'SSL/TLS sertifikatas'),
(UNHEX('88888888888888888888888888888887'), 'Kokia yra rekomenduojama slaptažodžio minimali ilgis?', '6 simboliai\n4 simboliai\n12+ simbolių\n8 simboliai\n10 simbolių', 'Security', 4, '12+ simbolių'),
(UNHEX('88888888888888888888888888888888'), 'Kas yra DDoS ataka?', 'Data Distribution over Servers\nDirect Database Output Service\nDistributed Denial of Service\nDynamic Domain Operating System\nDigital Data Output Security', 'Security', 5, 'Distributed Denial of Service'),
(UNHEX('88888888888888888888888888888889'), 'Kokia priemonė apsaugo nuo SQL injection?', 'Firewall\nAntivirus\nPrepared statements\nVPN\nProxy', 'Security', 5, 'Prepared statements'),
(UNHEX('8888888888888888888888888888888A'), 'Kas yra phishing?', 'Virusas\nTrojos arklys\nSukčiavimas el. paštu\nSpyware\nMalware', 'Security', 4, 'Sukčiavimas el. paštu'),

-- Data Structures
(UNHEX('99999999999999999999999999999991'), 'Kokia duomenų struktūra veikia LIFO principu?', 'Queue\nList\nStack\nTree\nGraph', 'Data Structures', 4, 'Stack'),
(UNHEX('99999999999999999999999999999992'), 'Kokia duomenų struktūra veikia FIFO principu?', 'Stack\nList\nQueue\nTree\nGraph', 'Data Structures', 4, 'Queue'),
(UNHEX('99999999999999999999999999999993'), 'Kokia yra binary tree maksimalus vaikų skaičius?', '1\n2\n3\n4\nNeribotai', 'Data Structures', 3, '2'),
(UNHEX('99999999999999999999999999999994'), 'Kokia duomenų struktūra naudoja key-value poras?', 'Array\nStack\nHash table\nQueue\nList', 'Data Structures', 4, 'Hash table'),
(UNHEX('99999999999999999999999999999995'), 'Koks yra array elemento pasiekimo laiko sudėtingumas?', 'O(n)\nO(log n)\nO(1)\nO(n^2)\nO(n log n)', 'Data Structures', 4, 'O(1)'),
(UNHEX('99999999999999999999999999999996'), 'Kokia duomenų struktūra geriausia elementų paieškai?', 'Array\nLinked list\nBinary search tree\nStack\nQueue', 'Data Structures', 5, 'Binary search tree'),
(UNHEX('99999999999999999999999999999997'), 'Kas yra linked list?', 'Masyvas\nDvejetainis medis\nSąrašas su nuorodomis\nHash lentelė\nGrafas', 'Data Structures', 3, 'Sąrašas su nuorodomis'),
(UNHEX('99999999999999999999999999999998'), 'Koks yra binary search laiko sudėtingumas?', 'O(n)\nO(1)\nO(log n)\nO(n^2)\nO(n log n)', 'Data Structures', 5, 'O(log n)'),
(UNHEX('99999999999999999999999999999999'), 'Kas yra graph?', 'Masyvas\nSąrašas\nDuomenų struktūra su node ir edge\nMedis\nLentelė', 'Data Structures', 4, 'Duomenų struktūra su node ir edge'),
(UNHEX('9999999999999999999999999999999A'), 'Kokia yra heap pagrindinė savybė?', 'Visada subalansuotas\nNeturi dublikatų\nParent >= children (max heap)\nCikliška struktūra\nFIFO tvarka', 'Data Structures', 5, 'Parent >= children (max heap)'),

-- Algorithms
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'), 'Koks yra bubble sort laiko sudėtingumas?', 'O(n log n)\nO(n)\nO(n^2)\nO(log n)\nO(1)', 'Algorithms', 4, 'O(n^2)'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA2'), 'Koks yra quick sort vidutinis laiko sudėtingumas?', 'O(n^2)\nO(n)\nO(n log n)\nO(log n)\nO(1)', 'Algorithms', 5, 'O(n log n)'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA3'), 'Kokia paieškos algoritmas greičiausias surūšiuotame masyve?', 'Linear search\nJump search\nBinary search\nInterpolation search\nExponential search', 'Algorithms', 4, 'Binary search'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA4'), 'Kas yra rekursija?', 'Ciklas\nSąlyga\nFunkcija kviečianti save\nMasyvas\nKintamasis', 'Algorithms', 3, 'Funkcija kviečianti save'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA5'), 'Koks yra merge sort laiko sudėtingumas?', 'O(n^2)\nO(n)\nO(n log n)\nO(log n)\nO(1)', 'Algorithms', 5, 'O(n log n)'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA6'), 'Kas yra dynamic programming?', 'Programavimo kalba\nDuomenų struktūra\nOptimizacijos metodas\nTinklo protokolas\nKodavimo stilius', 'Algorithms', 5, 'Optimizacijos metodas'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA7'), 'Koks yra insertion sort laiko sudėtingumas?', 'O(n log n)\nO(n)\nO(n^2)\nO(log n)\nO(1)', 'Algorithms', 4, 'O(n^2)'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA8'), 'Kas yra greedy algorithm?', 'Lėtas algoritmas\nPaieškos algoritmas\nAlgoritmas pasirenkantis geriausią lokalų sprendimą\nRūšiavimo algoritmas\nGrafo algoritmas', 'Algorithms', 5, 'Algoritmas pasirenkantis geriausią lokalų sprendimą'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA9'), 'Kas yra BFS (Breadth-First Search)?', 'Paieška gyliu\nDvejetainė paieška\nPaieška pločiu\nLinijinė paieška\nHash paieška', 'Algorithms', 5, 'Paieška pločiu'),
(UNHEX('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB'), 'Kas yra DFS (Depth-First Search)?', 'Paieška pločiu\nDvejetainė paieška\nPaieška gyliu\nLinijinė paieška\nHash paieška', 'Algorithms', 5, 'Paieška gyliu');