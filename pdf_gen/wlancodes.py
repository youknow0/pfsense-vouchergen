import csv
import sys
import os
import subprocess

input_filename = sys.argv[1]
validity = sys.argv[2]

codes = []
roll_number = 0

input_csv = open(input_filename, "rb" )
reader = csv.reader(input_csv)

tmp = reader.next()[0]
i = 1
while (tmp[-i] != ' '):
	i += 1
roll_number = int(tmp[-i:])


for row in reader:
	tmp = row[0]
	if tmp[0] == ' ':
		codes.append(tmp[1:])
	elif tmp[0] == '#':
		pass
	else:
		sys.exit(1)

input_csv.close()

for code_number in range(len(codes)):
	tmp = code_number +1
	template = file('template.tex', 'r').read()
	page = template % {'code' : codes[code_number], 'roll_number' : roll_number, 'validity' : validity, 'code_number' : tmp}
	file('result%00*d.tex' % (3,tmp), 'w').write(page)
	subprocess.call(['pdflatex', 'result%00*d.tex' % (3,tmp), '-interaction=nonstopmode'], shell=False)
	os.remove('result%00*d.tex' % (3,tmp))
	os.remove('result%00*d.aux' % (3,tmp))
	os.remove('result%00*d.log' % (3,tmp))
