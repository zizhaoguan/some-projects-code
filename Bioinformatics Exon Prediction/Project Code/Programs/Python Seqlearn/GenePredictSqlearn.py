# -*- coding: utf-8 -*-
"""
Created on Sat May  2 15:25:03 2020

@author: lidou
"""

from seqlearn.perceptron import StructuredPerceptron
from seqlearn.datasets import load_conll
from seqlearn.evaluation import bio_f_score
import numpy as np
'''
names of the files that wanted to be trained on
'''
training_list_name = ["seq3.txt","seq4.txt","seq5.txt","seq6.txt", "seq7.txt", "seq8.txt", "seq9.txt", "seq10.txt"]
training_seq = []

for name in training_list_name:
    
    file = open(name, "r")
    while True:
        s = file.read(1)
        if s =='':
            break
        else:
            if s.isalpha():
                training_seq.append(s)
        
    file.close()


file = open("training_seq.txt", "w")
for ch in training_seq:
    c = ch.join('   ')
    file.write(c)
    file.write('\n')
    
file.close()


clf = StructuredPerceptron()
def features(sequence, i):
     yield "word=" + sequence[i].lower()
     if sequence[i].isupper():
        yield "Uppercase"
     
        
        
X_train,y_train,lengths_train = load_conll("training_seq.txt", features)
clf = StructuredPerceptron()
clf.fit(X_train, y_train, lengths_train)

'''
names of the files that wanted to be test on
'''

predict_list_name = ["seq1.txt", "seq2.txt"]
predict_seq = []
output_list_name = ["prediction1.txt"]
count = 0

for name in predict_list_name:
    
    file = open(name, "r")

    while True:
        s = file.read(1)
        if s =='':
            break
        else:
            if s.isalpha():
                predict_seq.append(s)
            
    file.close()



    file = open("predict_seq.txt", "w")
    for ch in predict_seq:
        c = ch.join('   ')
        file.write(c)
        file.write('\n')
    
    file.close()


    X_test, y_test, lengths_test = load_conll("predict_seq.txt", features)
    y_pred = clf.predict(X_test, lengths_test)
    print(bio_f_score(y_test, y_pred))

    print(lengths_test)
    

    print(X_test)
    print(y_test)   

    
    file = open(output_list_name[count],"w")
    for i in y_pred:
        file.write(i)
        #file.write(np.array2string(i, precision=2, separator=','))

    file.close()
    count+=1
    predict_seq.clear()
