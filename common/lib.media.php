<?php

// common to both fe/be
// this is meta data that drives decisions and actions in fe/be
/*
'mime key' => array(
  +'commonExt' => [],
  +'getMeta'
  +'detection'
    +'detectFile'
  +'exif'
  +'stripExifFile'
  // keep exif File?
  +'thumbnail' =>
    +'generate file' bool
    +'js settings'
    +'viewer'
    +viewerSize
  +'main' => ^ thumbnail
  +interactive (drives close option)
  +hasSound bool
  +loopable bool
  +hover bool
  +hoverAlt (movie, animated gif, audio, img)
  +hasPlayPause bool
  +requiresViewerLib
  +download?
  +watch?
)
*/

// frontend
$media_viewers = array(
  'none' => array(
    'viewerFile' => false,
    'interactive' => false,
    'cssOnlyExpandable' => false,
    'hoverable' => false,
  ),
  'htmlimg' => array(
    'viewerFile' => false,
    'interactive' => false,
    'cssOnlyExpandable' => true,
    'hoverable' => true,
    // minimumViewerSizes?
  ),
  // html5media?
  'html5video' => array(
    'viewerFile' => false,
    'interactive' => true,
    'hasPlayPause' => true,
    'cssOnlyExpandable' => false,
    'hoverable' => true,
    'loopable' => true,
    // soundcapable?
    'hasSound' => true,
  ),
  'gltf' => array(
    'viewerFile' => false,
    'interactive' => true,
    'cssOnlyExpandable' => false,
    'hoverable' => false,
    // minimumViewerSizes?
  ),
);

$jpeg = array(
  'common' => array(
    'commonExt' => array('jpg', 'jpeg'),
  ),
  'be' => array(
    'verifyFile' => false,
    'getMeta' => false, // exif
    'thumbnail' => array(
      // sizing?
      'generateFile' => false, // ffmpeg image nailer
      'hoverAltGeneratorFile' => false,
      'consumeGeneratorFile' => false,
    ),
    'consume' => array(
      'stripMetaFile' => false,
    ),
  ),
  'fe' => array(
    'thumbnail' => array(
      // sizing? well backend should communicate this
      'viewer' => 'htmlimg',
    ),
    'consume' => array(
    ),
  ),
);

$media_registry = array(
  // image/jpeg handling?
  'image/jpg' => $jpeg,
  'image/jpeg' => $jpeg,
  'image/png' => array(
    'common' => array(
    ),
    'be' => array(

    ),
    'fe' => array(
      'thumbnail' => array(
        'viewer' => 'htmlimg',
      ),
      'consume' => array(
      ),
    )
  ),
  'image/webp' => array(
    'common' => array(
    ),
    'be' => array(

    ),
    'fe' => array(
      'thumbnail' => array(
        'viewer' => 'htmlimg',
      ),
      'consume' => array(
      ),
    )
  ),
  'video/webm' => array(
    'common' => array(
    ),
    'be' => array(

    ),
    'fe' => array(
      'thumbnail' => array(
        'viewer' => 'html5video',
      ),
      'consume' => array(
      ),
    )
  ),
  // mp3?
  'audio/mpeg' => array(
    'common' => array(
    ),
    'be' => array(

    ),
    'fe' => array(
      'thumbnail' => array(
        'viewer' => 'html5video',
      ),
      'consume' => array(
      ),
    )
  ),

  'application/octet-stream' => array(
    'common' => array(
    ),
    'be' => array(

    ),
    'fe' => array(
      'thumbnail' => array(
        'viewer' => 'none',
      ),
      'consume' => array(
      ),
    )
  ),
  'model/gltf-binary' => array(
    'common' => array(
    ),
    'be' => array(

    ),
    'fe' => array(
      'thumbnail' => array(
        'viewer' => 'gltf',
      ),
      'consume' => array(
      ),
    )
  ),
);

?>