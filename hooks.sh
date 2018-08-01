#!/bin/bash

if [ -d ".git" ];
then
    echo "Local Environment: Copy hooks/* to .git/hooks/";
    cp hooks/* .git/hooks/;
    chmod +x .git/hooks/pre-commit
fi
