import numpy as np
from hmmlearn.hmm import GaussianHMM
from hmmlearn import hmm
#from seqlearn.datasets import load_conll

'''
put the names of the training set into a list
'''
training_list_names = ["seq3.txt","seq4.txt","seq5.txt","seq6.txt", "seq7.txt", "seq8.txt", "seq9.txt","seq10.txt"]
training_list = []

'''
combine the training set and save into a single list
'''
for name in training_list_names:
        file = open(name, "r") #read the sequence from file
        while True:
            ch = file.read(1)
            if ch == "A" or ch=="a":
                training_list.append(0)
            elif ch == "T" or ch=="t":
                training_list.append(1)
            elif ch == "C" or ch=="c":
                training_list.append(2)
            elif ch == "G" or ch=="g":
                training_list.append(3)
            elif not ch: # equals if ch == ""
                break
        file.close();



states = ["exon", "intron"]
n_states = len(states)

observations = ["A", "C", "T", "G"]
n_observations = len(observations)

'''
initialize a model of hmm.GaussianHMM
'''
model = hmm.GaussianHMM(2, "full", n_iter=10000, tol=0.0001)
best_score = -100000

X1 = np.array(training_list)

model.fit(X1.reshape(-1,1))
best_model = model

for i in range(1, 1):
    
    model.fit(X1.reshape(-1,1))

    print(model.startprob_, '\n')
    print(model.transmat_, '\n')
    
    print(model.score(X1.reshape(-1,1)))

    if model.score(X1.reshape(-1,1))> best_score:
        best_score = model.score(X1.reshape(-1,1))
        best_model = model
        

scoring_list_names = ["seq1.txt", "seq2.txt"]
scoring_list = []

'''
put the name of test sets in the list 
'''
predict_list_name = ["prediction1.txt", "prediction2.txt"]
count = 0
for name in scoring_list_names:
        file = open(name, "r") #read the sequence from file
        while True:
            ch = file.read(1)
            if ch == "A" or ch=="a":
                scoring_list.append(0)
            elif ch == "T" or ch=="t":
                scoring_list.append(1)
            elif ch == "C" or ch=="c":
                scoring_list.append(2)
            elif ch == "G" or ch=="g":
                scoring_list.append(3)
            elif not ch: # equals if ch == ""
                break
        file.close();


        Y = np.array(scoring_list)
        Z = Y.reshape(-1, 1)


        prediction_list = best_model.predict(Z, lengths=None)

        file = open(predict_list_name[count],"w")
        count+=1
        for i in prediction_list:
            file.write(np.array2string(i, precision=2, separator=','))
        file.close()
