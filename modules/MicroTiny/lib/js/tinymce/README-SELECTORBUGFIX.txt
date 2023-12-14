After substituting the un-minimised tinymce 6.8.1 script, I found:

const updateBlockStateOnChildren = (schema, scope) => {
  const transparentSelector = makeSelectorFromSchemaMap(schema.getTransparentElements()); <<< creates unacceptable selector 
  const blocksSelector = makeSelectorFromSchemaMap(schema.getBlockElements());
  return filter$5(scope.querySelectorAll(transparentSelector), transparent => updateTransparent(blocksSelector, transparent)); <<< failure point
};

ORIGINAL transparentSelector value, REPORTED INVALID
"map:not(svg map),canvas:not(svg canvas),del:not(svg del),ins:not(svg ins),a:not(svg a)"
ACCEPTABLE ALTERNATIVE transparentSelector value, ALL GOOD WITH THIS
"map:not(:is(svg map)),canvas:not(:is(svg canvas)),del:not(:is(svg del)),ins:not(:is(svg ins)),a:not(:is(svg a))"
This effect might be browser-specific, I suppose.

See https://developer.mozilla.org/en-US/docs/Web/CSS/:not#description
