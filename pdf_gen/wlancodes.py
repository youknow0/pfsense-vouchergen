import csv
import sys
import os
import subprocess

if len(sys.argv) != 4:
	print "Usage: %s [input] [validity in hours] [output]" % sys.argv[0]
	sys.exit(1)

input_filename = sys.argv[1]

# .pdf will automatically be appended to output_filename
output_filename = sys.argv[2]
validity = sys.argv[3]


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

output = open(output_filename, "w")

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

subprocess.call(['pdflatex', '--jobname=' + output_filename, output_filename, '-interaction=nonstopmode'], shell=False)

try:
	os.remove(output_filename)
	os.remove(output_filename + ".aux")
	os.remove(output_filename  + ".log")
except OSError:
	print "Could not remove temp files!"
