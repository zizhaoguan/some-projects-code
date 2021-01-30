#include <iostream>
#include <fstream>
#include "Windows.h"
#include "HmmModel.h"

using namespace std;

HmmModel* training(int*, int);
HmmModel* trainingWithoutInit(HmmModel*, int*, int);
double computeLog(int, HmmModel*);
void predictStates(HmmModel*);

int main(int argc, char* argv[])
{
	int i, j;

	ifstream readFile;  
	ofstream writeFile;

	char writeFileName[100];

	int numOfTS; //number of training sets
	cout << "How many training sets do you want?\n";

	cin >> numOfTS;
	int** NTseq = new int*[numOfTS]; //initialize nucleotide sequence for each training set
	int* countOfNT = new int[numOfTS];
	//read the training sequence
	for (i = 0; i < numOfTS; i++) {
		
		readFile.open(argv[i + 1], ios::in);
		if (!readFile) {
			cout << "cannot open" << endl;
		}
		else {
			cout << "open" << endl;
		}
		sprintf_s(writeFileName, "%d.txt", i + 1);
		writeFile.open(writeFileName, ios::out);
		if (!writeFile) {
			cout << "cannot open" << endl;
		}
		char nt;
		countOfNT[i] = 0;
		/*
			A or a = 0
			T or t = 1
			C or c = 2
			G or g = 3
			*/
		nt = readFile.peek();
		//write the int format of training sequence into a file
		while (nt != EOF) {
			nt = readFile.get();
			if (nt == 'A' || nt == 'a') {
				//cout << nt << endl;
				writeFile << 0 << endl;
				countOfNT[i]++;
			}
			else if (nt == 'T' || nt == 't') {
				//cout << nt << endl;
				writeFile << 1 << endl;
				countOfNT[i]++;
			}
			else if (nt == 'C' || nt == 'c') {
				//cout << nt << endl;
				writeFile << 2 << endl;
				countOfNT[i]++;
			}
			else if (nt == 'G' || nt == 'g') {
				//cout << nt << endl;
				writeFile << 3 << endl;
				countOfNT[i]++;
			}
			nt = readFile.peek();
		}

		readFile.close();
		writeFile.close();
		//store the integer sequence into NTseq[i]
		NTseq[i] = new int[countOfNT[i]];
		readFile.open(writeFileName, ios::in);

		//start to store the nucleotide into the NTseq as number format;
		j = 0;
		while (!readFile.eof()) {

			if (j < countOfNT[i]) {
				readFile >> NTseq[i][j++];
			}
			else {
				//end of the prior bit before EOF
				readFile.ignore();
			}
		}
		readFile.close();
	}

	//start to set up training model(s);
	int numOfModel;
	cout << "how many training models do you want to generate?\n";
	cin >> numOfModel;

	//create some models
	HmmModel** trainingModel = new HmmModel * [numOfModel];

	//train the model on each training set
	for (i = 0; i < numOfModel; i++){

		trainingModel[i] = training(NTseq[0], countOfNT[0]);
		
		for (j = 1; j < numOfTS; j++){
			trainingModel[i] = trainingWithoutInit(trainingModel[i],NTseq[j], countOfNT[j]);
		}
	}

	//find the best model;
	int bestTrainingModel;
	double bestProlog = -1000000;

	for (int i = 0; i < numOfModel; i++)
	{
		cout << "prolog of [" << i << "] is " << trainingModel[i]->getOldProlog() << endl;
		if (trainingModel[i]->getOldProlog() >= bestProlog)
		{
			bestProlog = trainingModel[i]->getOldProlog();
			bestTrainingModel = i;
		}
	}
	cout << "best model with prolog " << trainingModel[bestTrainingModel]->getOldProlog() << " is model " << bestTrainingModel << endl;

	cout << "The pi, A and B matrix of best model:\n";
	trainingModel[bestTrainingModel]->printPi();
	trainingModel[bestTrainingModel]->printAMatrixWFile(1);
	trainingModel[bestTrainingModel]->printBMatrixWFile(1);


	cout << "do you want to score? (Y/N)" << endl;
	char choice;
	cin >> choice;
	//predict the hidden state of the test set
	while (choice == 'Y') { predictStates(trainingModel[bestTrainingModel]); }
	for (i = 0; i < numOfTS; i++) { trainingModel[i]->freeAllPtr(); }
}

//training a new model, will initialize it with random value first
HmmModel* training(int* NTseq, int countOfNT)
{
	HmmModel* model = new HmmModel(2, 4);

	model->initializeModel(NTseq);
	model->printPi();
	model->printAMatrixWFile(0);
	model->printBMatrixWFile(0);
	model->printRowSum();
	//initialize the alpha, beta, gammas, and digammas matrix for the model
	model->setData(countOfNT);
	//set the minimum iteration
	int minIter = 100;
	int iter = 0;
	//the minimum error allowed
	double err = (double)0.001;

	while (!(model->checkIfGoodModel())) {
		cout << "re-estimate model..." << iter++ << " times\n";
		model->computeAlphaPass(NTseq);
		model->computeBetaPass(NTseq);
		model->computeGammas(NTseq);
		model->reestimate(NTseq);

		model->setNewProlog(computeLog(model->getT(), model));
		cout << "prolog = " << model->getNewProlog() << endl;
		model->setDiff((model->getOldProlog()) - (model->getNewProlog()));

		if (model->getDiff() < 0) { model->setDiff(-(model->getDiff())); }
		if (iter < minIter || model->getDiff() > err)
		{
			model->setOldProlog(model->getNewProlog());
		}
		else { model->setGoodModel(true); }
	}
	model->printPi();
	model->printAMatrixWFile(0);
	model->printBMatrixWFile(0);
	model->printRowSum();
	return model;
}

// almost same with training function but without initialize the pi, A and B matrix
HmmModel* trainingWithoutInit(HmmModel* model, int* NTseq, int countOfNT)
{

	model->setData(countOfNT);
	int minIter = 100;
	int iter = 0;

	double err = (double)0.001;

	while (!(model->checkIfGoodModel())) {
		cout << "re-estimate model..." << iter++ << " times\n";
		model->computeAlphaPass(NTseq);
		model->computeBetaPass(NTseq);
		model->computeGammas(NTseq);
		model->reestimate(NTseq);

		model->setNewProlog(computeLog(model->getT(), model));
		cout << "prolog = " << model->getNewProlog() << endl;
		model->setDiff((model->getOldProlog()) - (model->getNewProlog()));

		if (model->getDiff() < 0) { model->setDiff(-(model->getDiff())); }
		if (iter < minIter || model->getDiff() > err)
		{
			model->setOldProlog(model->getNewProlog());
		}
		else { model->setGoodModel(true); }
	}
	model->printPi();
	model->printAMatrixWFile(0);
	model->printBMatrixWFile(0);
	model->printRowSum();
	return model;
}
//find out the most likely states sequence for the test set
void predictStates(HmmModel* trainingModel)
{
	int i, j;
	//open the training set file and translate the opcode to integer
	ifstream readFile;
	char inFileName[100];
	char preditionFileName[100];
	cout << "Name of prediction set? (only the name, without '.txt')\n";
	cin >> inFileName;

	sprintf_s(preditionFileName, "%s.txt", inFileName);
	//read the nucleotide from the test set
	readFile.open(preditionFileName, ios::in);
	while (!readFile)
	{
		cout << "wrong file name, tell me again\n";	
		cin >> inFileName;
		sprintf_s(preditionFileName, "%s.txt", inFileName);
		readFile.open(preditionFileName, ios::in);
	}

	//create a out put file to store the translate opcode;
	ofstream writeFile;
	
	writeFile.open("nucleotide.txt", ios::out);
	
	char nt = readFile.peek();
	int countOfNT = 0;
	//translate the nucleotide that read from the test set into int
	//and write them into a new file
	while (nt != EOF) {
		nt = readFile.get();
		if (nt == 'A' || nt == 'a') {
			//cout << nt << endl;
			writeFile << 0 << endl;
			countOfNT++;
		}
		else if (nt == 'T' || nt == 't') {
			//cout << nt << endl;
			writeFile << 1 << endl;
			countOfNT++;
		}
		else if (nt == 'C' || nt == 'c') {
			//cout << nt << endl;
			writeFile << 2 << endl;
			countOfNT++;
		}
		else if (nt == 'G' || nt == 'g') {
			//cout << nt << endl;
			writeFile << 3 << endl;
			countOfNT++;
		}
		nt = readFile.peek();
	}
	readFile.close();
	writeFile.close();
	//read the output file that contain the integer "nucleotide", then allocate enough integer space to store it,
	//and let a ptr point to it.
	
	readFile.open("nucleotide.txt", ios::in);

	int* NTseq = new int[countOfNT];
	
	i = 0;
	while (!readFile.eof()) {

		if (i < countOfNT) {
			readFile >> NTseq[i++];
		}
		else {
			//end of the prior bit before EOF
			readFile.ignore();
		}
	}
	readFile.close();
	//initialize a new model for the test set but copy the values from pi, A, B matrix of the training model
	HmmModel* model = new HmmModel(2, 4);
	model->initializeModel(NTseq);
	model->copyPi(trainingModel);
	model->copyA(trainingModel);
	model->copyB(trainingModel);
	model->printBMatrixWFile(0);
	
	//start to score
	model->setData(countOfNT);
	model->computeAlphaPass(NTseq);
	model->computeBetaPass(NTseq);
	model->computeGammas(NTseq);

	//write the file that conclude the biggest probability of state in each time t;
	char outFileName[100] = "";
	int fileIndex;
	cout << "number of the output file you want?\n";
	cin >> fileIndex;
	sprintf_s(outFileName, "prediction%d.txt", fileIndex);

	writeFile.open(outFileName, ios::out);

	//use state with the highest probability at each position as the prediciton state
	double** gammas = model->getGammas();
	for (i = 0; i < countOfNT; i++) {
		double maxPro = gammas[i][0];
		int bestState = 0;
		for (j = 0; j < 2; j++){
			if (gammas[i][j] > maxPro) {
				maxPro = gammas[i][j];
				bestState = j;
			}
		}
		writeFile << bestState;
	}
	writeFile.close();

	//model->freeAllPtr();
}

double computeLog(int T, HmmModel* aModel)
{
	double logProb = 0;
	for (int i = 0; i < T; i++) { logProb = logProb + log((aModel->getScale())[i]); }
	logProb = -logProb;
	return logProb;
}