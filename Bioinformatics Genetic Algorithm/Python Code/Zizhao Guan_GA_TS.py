# -*- coding: utf-8 -*-
"""
Created on Tue Oct  6 09:39:40 2020

@author: lidou
"""

import pandas
from CitySeq import*
from Population import*
import random
import math
import os

#q method read from the csv file and store distances in a 2D list
def readCSV(filename):
    distanceArr = [[] for x in range(8)] 
    df = pandas.read_csv(filename, index_col=0)
    df.columns = [0,1,2,3,4,5,6,7]
    for x in range(8):
        for y in range(8):
            #print(df[2][3])
            distanceArr[x].append(df[y][x])
    

    for x in range(8):
        for y in range(8):
            print(distanceArr[x][y],end='  ')
        print("\n")
    return distanceArr
#end of the readCSV method
    

#initialize the population
def initializePop(seq, distanceArr):
    aPopulation = Population()
    
    for x in range(100):
        copy = list(seq)
        random.shuffle(copy)
        cities = CitySeq(copy)
        cities.calDistance(distanceArr)
        
        print(cities.getSeq(),end='  ')
        print(cities.getDistance())
        aPopulation.addIndividuals(cities)
        
    aPopulation.calAllScores()
    return aPopulation 
#end of initialize population method

#method of selection
def selection(aPopulation, distanceArr):
    
    newPopulation = Population()
    aPopulation.calAllScores();
    mean = aPopulation.getAverage()
    print("average = ", mean)
    selected = 0
    
    sum =0
    for x in aPopulation.getPopulation():
        if x[1] <= mean:
            print("rate = ", mean/x[1])
            sum+=mean/x[1]
    print("sum = ", sum)     
    
    for x in aPopulation.getPopulation():
        if x[1] <= mean:
            rate = mean/x[1]
            print("weight = ", rate/sum)
            weight_int = int(round(rate*100/sum))
            print("acutal weight = ", weight_int)
            selected+=1
            for y in range(weight_int):
                copy = list(x[0].getSeq())
                cities = CitySeq(copy)
                cities.calDistance(distanceArr)
                print("adding: ", cities,"\nsequence: ", cities.getSeq(), "\ndistance: ", cities.getDistance())
                newPopulation.addIndividuals(cities)
    newPopulation.setSelected(selected)
    print("\ndone with selection\n")
    newPopulation.printPopulation()
    print("\ntotal individuals = ", len(newPopulation.individuals))
    newPopulation.calAllScores()
    return newPopulation
#end of selection method    
        
#crossover method
def crossover(parentPop, distanceArr):
    individuals = parentPop.getPopulation()
    childPopulation = Population()
    for x in range(100):
        randomNumA = random.randint(0, len(individuals)-1)
        print("random parent A = ", randomNumA)
        print("random parent A: ", individuals[randomNumA])
        print("random parent A seqence: ", individuals[randomNumA][0].getSeq())
        randomNumB = random.randint(0, len(individuals)-1)
        print("random parent B = ", randomNumB)
        print("random parent B: ", individuals[randomNumB])
        print("random parent B seqence: ", individuals[randomNumB][0].getSeq())
        
        crossoverPosA = random.randint(0,7)
        crossoverPosB = random.randint(0,7)
        while abs(crossoverPosA-crossoverPosB)<=1 :
            crossoverPosB = random.randint(0,7)
        print("crossover postion: ",crossoverPosA, " and ",  crossoverPosB)
        newSeqA = list()
        #newSeqB = list()
        
        if crossoverPosA < crossoverPosB:
            subSeqA = list()
            subSeqB = list()
            pos = (crossoverPosB+1)%8
            for x in range(crossoverPosA, crossoverPosB+1):
                subSeqA.append(individuals[randomNumA][0].getSeq()[x])
            for x in range(crossoverPosB+1, 8):
                while individuals[randomNumB][0].getSeq()[pos] in subSeqA:
                    pos+=1
                    pos = pos%8
                subSeqA.append(individuals[randomNumB][0].getSeq()[pos])
                pos+=1
                pos = pos%8
                
            for x in range(0, crossoverPosA):
                while individuals[randomNumB][0].getSeq()[pos] in subSeqA:
                    pos+=1
                    pos = pos%8
                subSeqB.append(individuals[randomNumB][0].getSeq()[pos])
                pos+=1
                pos = pos%8
            newSeqA = subSeqB + subSeqA
        else:
            subSeqA = list()
            subSeqB = list()
            pos = (crossoverPosA+1)%8
            for x in range(crossoverPosB, crossoverPosA+1):
                subSeqA.append(individuals[randomNumA][0].getSeq()[x])
            for x in range(crossoverPosA+1, 8):
                while individuals[randomNumB][0].getSeq()[pos] in subSeqA:
                    pos+=1
                    pos = pos%8
                subSeqA.append(individuals[randomNumB][0].getSeq()[pos])
                pos+=1
                pos = pos%8
                
            for x in range(0, crossoverPosB):
                while individuals[randomNumB][0].getSeq()[pos] in subSeqA:
                    pos+=1
                    pos = pos%8
                subSeqB.append(individuals[randomNumB][0].getSeq()[pos])
                pos+=1
                pos = pos%8
            newSeqA = subSeqB + subSeqA
        
        print("new seq A: ",newSeqA)
        
        cities = CitySeq(newSeqA)
        cities.calDistance(distanceArr)
        childPopulation.addIndividuals(cities)
        #os.system('pause')
    print("\ndone with crossover...")
    childPopulation.calAllScores()
    childPopulation.printPopulation()
    print("\ntotal children: ", len(childPopulation.getPopulation()))
    return childPopulation
#end of crossover method

#mutation method     
def mutation(aPopulation, distanceArr):
    
    for x in range(5):
        randomChildNum = random.randint(0,9)
        print("outside class: \nbefore swap:", aPopulation.getPopulation()[randomChildNum][0].getSeq())
        aPopulation.swapIndividualSeq(randomChildNum, distanceArr)
        print("outside class: \nafter swap:", aPopulation.getPopulation()[randomChildNum][0].getSeq())
        print()
    aPopulation.calAllScores()
    aPopulation.printPopulation()
#end of mutation method                   
   

     
distArr = readCSV('TS_Distances_Between_Cities.csv')

originalSeq = [0,1,2,3,4,5,6,7]

aPop = initializePop(originalSeq, distArr)


print()

aPop.printPopulation()
'''
for x in aPop.getPopulation():
    print(x[0].getSeq())
'''
print("average = ",aPop.getAverage())
print("median socre = ", aPop.getMedianScore())
print("standard score =", aPop.getSTDScores())



#os.system('pause')

oldMinDist = 10000000
newMinDist = 9999999
minSeq = []
loopCount =0;
terminate = False

wFile = open('Zizhao Guan_GA_TS_Info.txt', 'w')
wFile.close()

while True:
    
    wFile = open('Zizhao Guan_GA_TS_Info.txt', 'a')
    loopCount+=1
    
    newPop = selection(aPop, distArr)
    childPop = crossover(newPop, distArr)
    mutation(childPop, distArr)
    wFile.write("{}.Population Size: {} for iteration {}\n".format(loopCount, len(childPop.getPopulation()), loopCount))
    
    wFile.write("Average fitness score = {}\nMedian fitness score ={}\nSTD of fitness scores ={}\nSize of the selected subset of the population = {}\n\n"
                .format(childPop.getAverage(), childPop.getMedianScore(),childPop.getSTDScores(), newPop.getSelected()))
        
    if childPop.getPopulation()[0][1] <= newMinDist:
        newMinDist = childPop.getPopulation()[0][1]
        minSeq = list(childPop.getPopulation()[0][0].getSeq())
    
    if loopCount>100 and abs(oldMinDist - newMinDist)<0.001:
        break
    else:
        oldMinDist = newMinDist
        aPop = childPop

wFile.close()

print("the program has run ", loopCount,
      " until terminate...\nthe minimun distance is:", newMinDist,
      "\nthe sequence is: ", minSeq)   

wFile = open("Zizhao Guan_GA_TS_Result.txt", 'w')
wFile.close()
count = 1;
for x in minSeq:
    wFile = open("Zizhao Guan_GA_TS_Result.txt", 'a')
    
    if x==0:
        cityName = "London"
    elif x==1:
        cityName = "Venice"
    elif x==2:
        cityName = "Dunedin"
    elif x==3:
        cityName = "Singapore"
    elif x==4:
        cityName = "Beijing"
    elif x==5:
        cityName = "Phoenix"
    elif x==6:
        cityName = "Tokyo"
    elif x==7:
        cityName = "Victoria"
    else:
        cityName = "error"
    wFile.write("{}. {}\n".format(count, cityName))
    count+=1
wFile.write("the program has run {} until terminate...\nthe minimun distance is: {}".format(loopCount, newMinDist)) 
  
wFile.close()
    
    


    