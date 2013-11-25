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

header = open("header.tex", "r")
template_tmp = open("template.tex", "r")
template = template_tmp.read()
footer = open("footer.tex", "r")

output = open("output.tex", "w")

output.write(header.read())

for code_number in range(len(codes)):
	tmp = code_number +1
	page = template % {'code' : codes[code_number], 'roll_number' : roll_number, 'validity' : validity, 'code_number' : tmp}
	output.write(page)	

output.write(footer.read())

header.close()
template_tmp.close()
footer.close()
output.close()

subprocess.call(['pdflatex', 'output.tex', '-interaction=nonstopmode'], shell=False)
os.remove('output.tex')
os.remove('output.aux')
os.remove('output.log')
