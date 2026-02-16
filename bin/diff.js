import * as jsondiffpatch from 'jsondiffpatch';
import * as consoleFormatter from "jsondiffpatch/formatters/console";

import {diff_match_patch} from '@dmsnell/diff-match-patch';

import {readFileSync} from "fs";

const jsondiffpatchInstance = jsondiffpatch.create({
    objectHash: function (obj, index) {
        return obj.name || '$$index:' + index;
    },
    arrays: {
        detectMove: true,
        includeValueOnMove: false,
    },
    textDiff: {
        diffMatchPatch: diff_match_patch,
        minLength: 60,
    },
    cloneDiffValues: false,
    omitRemovedValues: false
});

const left = JSON.parse(readFileSync(process.argv[2], 'utf-8'));
const right = JSON.parse(readFileSync(process.argv[3], 'utf-8'));

const delta = jsondiffpatchInstance.diff(left, right);
const output = consoleFormatter.format(delta);
console.log(output);

