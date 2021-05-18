<?php

namespace ABadCafe\PDE;

const CLASS_MAP = [
  'ABadCafe\\PDE\\IDisplay' => '/IDisplay.php',
  'ABadCafe\\PDE\\IRoutine' => '/IRoutine.php',
  'ABadCafe\\PDE\\IParameterisable' => '/IParameterisable.php',
  'ABadCafe\\PDE\\Util\\Vec3F' => '/util/Vec3F.php',
  'ABadCafe\\PDE\\Display\\PlainASCII' => '/display/PlainASCII.php',
  'ABadCafe\\PDE\\Display\\RGBASCIIOverRGB' => '/display/RGBASCIIOverRGB.php',
  'ABadCafe\\PDE\\Display\\DoubleVerticalRGB' => '/display/DoubleVerticalRGB.php',
  'ABadCafe\\PDE\\Display\\Factory' => '/display/Factory.php',
  'ABadCafe\\PDE\\Display\\BasicRGB' => '/display/BasicRGB.php',
  'ABadCafe\\PDE\\Display\\ASCIIOverRGB' => '/display/ASCIIOverRGB.php',
  'ABadCafe\\PDE\\Display\\RGBASCII' => '/display/RGBASCII.php',
  'ABadCafe\\PDE\\Display\\IANSIControl' => '/display/common/IANSIControl.php',
  'ABadCafe\\PDE\\Display\\IPixelled' => '/display/common/IPixelled.php',
  'ABadCafe\\PDE\\Display\\TPixelled' => '/display/common/TPixelled.php',
  'ABadCafe\\PDE\\Display\\IASCIIArt' => '/display/common/IASCIIArt.php',
  'ABadCafe\\PDE\\Display\\TASCIIArt' => '/display/common/TASCIIArt.php',
  'ABadCafe\\PDE\\Display\\Base' => '/display/common/Base.php',
  'ABadCafe\\PDE\\Display\\BaseAsyncASCIIWithRGB' => '/display/common/BaseAsyncASCIIWithRGB.class.php',
  'ABadCafe\\PDE\\Display\\IAsynchronous' => '/display/common/IAsynchronous.php',
  'ABadCafe\\PDE\\Display\\TInstrumented' => '/display/common/TInstrumented.php',
  'ABadCafe\\PDE\\Display\\TAsynchronous' => '/display/common/TAsynchronous.php',
  'ABadCafe\\PDE\\Display\\ICustomChars' => '/display/common/ICustomChars.php',
  'ABadCafe\\PDE\\Graphics\\Palette' => '/graphics/Palette.php',
  'ABadCafe\\PDE\\Graphics\\Blitter' => '/graphics/Blitter.php',
  'ABadCafe\\PDE\\Graphics\\IDrawMode' => '/graphics/IDrawMode.php',
  'ABadCafe\\PDE\\Graphics\\IPixelBuffer' => '/graphics/IPixelBuffer.php',
  'ABadCafe\\PDE\\Graphics\\Image' => '/graphics/Image.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\IMode' => '/graphics/blitter_modes/IMode.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\Inverse' => '/graphics/blitter_modes/Inverse.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\CombineMultiply' => '/graphics/blitter_modes/CombineMultiply.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\CombineAnd' => '/graphics/blitter_modes/CombineAnd.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\Base' => '/graphics/blitter_modes/Base.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\CombineXor' => '/graphics/blitter_modes/CombineXor.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\Replace' => '/graphics/blitter_modes/Replace.php',
  'ABadCafe\\PDE\\Graphics\\BlitterModes\\CombineOr' => '/graphics/blitter_modes/CombineOr.php',
  'ABadCafe\\PDE\\Audio\\PCMOutput' => '/audio/PCMOutput.php',
  'ABadCafe\\PDE\\Audio\\IConfig' => '/audio/IConfig.php',
  'ABadCafe\\PDE\\Audio\\Signal\\FixedMixer' => '/audio/signal/FixedMixer.php',
  'ABadCafe\\PDE\\Audio\\Signal\\IStream' => '/audio/signal/IStream.php',
  'ABadCafe\\PDE\\Audio\\Signal\\IWaveform' => '/audio/signal/IWaveform.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Packet' => '/audio/signal/Packet.php',
  'ABadCafe\\PDE\\Audio\\Signal\\TPacketIndexAware' => '/audio/signal/Packet.php',
  'ABadCafe\\PDE\\Audio\\Signal\\IOscillator' => '/audio/signal/IOscillator.php',
  'ABadCafe\\PDE\\Audio\\Signal\\IEnvelope' => '/audio/signal/IEnvelope.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Oscillator\\Base' => '/audio/signal/oscillator/Base.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Oscillator\\Sound' => '/audio/signal/oscillator/Sound.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Oscillator\\LFO' => '/audio/signal/oscillator/LFO.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Waveform\\Triangle' => '/audio/signal/waveform/Triangle.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Waveform\\Square' => '/audio/signal/waveform/Square.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Waveform\\Sine' => '/audio/signal/waveform/Sine.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Waveform\\WhiteNoise' => '/audio/signal/waveform/WhiteNoise.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Waveform\\Saw' => '/audio/signal/waveform/Saw.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Envelope\\Shape' => '/audio/signal/envelope/Shape.php',
  'ABadCafe\\PDE\\Audio\\Signal\\Envelope\\DecayPulse' => '/audio/signal/envelope/DecayPulse.php',
  'ABadCafe\\PDE\\Routine\\NoOp' => '/routine/NoOp.php',
  'ABadCafe\\PDE\\Routine\\Factory' => '/routine/Factory.php',
  'ABadCafe\\PDE\\Routine\\SimpleLine' => '/routine/2D/SimpleLine.php',
  'ABadCafe\\PDE\\Routine\\RGBPulse' => '/routine/2D/RGBPulse.php',
  'ABadCafe\\PDE\\Routine\\RGBImage' => '/routine/2D/RGBImage.php',
  'ABadCafe\\PDE\\Routine\\RGBFire' => '/routine/2D/RGBFire.php',
  'ABadCafe\\PDE\\Routine\\TapeLoader' => '/routine/2D/TapeLoader.php',
  'ABadCafe\\PDE\\Routine\\RGBPersistence' => '/routine/2D/RGBPersistence.php',
  'ABadCafe\\PDE\\Routine\\StaticNoise' => '/routine/2D/StaticNoise.php',
  'ABadCafe\\PDE\\Routine\\Toroid' => '/routine/3D/Toroid.php',
  'ABadCafe\\PDE\\Routine\\Voxel' => '/routine/3D/Voxel.php',
  'ABadCafe\\PDE\\Routine\\Raytrace' => '/routine/3D/Raytrace.php',
  'ABadCafe\\PDE\\Routine\\Tunnel' => '/routine/3D/Tunnel.php',
  'ABadCafe\\PDE\\Routine\\IResourceLoader' => '/routine/common/IResourceLoader.php',
  'ABadCafe\\PDE\\Routine\\TResourceLoader' => '/routine/common/TResourceLoader.php',
  'ABadCafe\\PDE\\Routine\\Base' => '/routine/common/Base.php',
  'ABadCafe\\PDE\\System\\ILoader' => '/system/ILoader.php',
  'ABadCafe\\PDE\\System\\IRateLimiter' => '/system/IRateLimiter.php',
  'ABadCafe\\PDE\\System\\Context' => '/system/Context.php',
  'ABadCafe\\PDE\\System\\RateLimiter\\Adaptive' => '/system/rate_limiter/Adaptive.php',
  'ABadCafe\\PDE\\System\\RateLimiter\\Simple' => '/system/rate_limiter/Simple.php',
  'ABadCafe\\PDE\\System\\Definition\\TDefinition' => '/system/definition/TDefinition.php',
  'ABadCafe\\PDE\\System\\Definition\\Display' => '/system/definition/Display.php',
  'ABadCafe\\PDE\\System\\Definition\\Event' => '/system/definition/Event.php',
  'ABadCafe\\PDE\\System\\Definition\\Routine' => '/system/definition/Routine.php',
  'ABadCafe\\PDE\\System\\Loader\\JSON' => '/system/loader/JSON.php',
  '\\Scene' => '/tests/raytrace.php',
];