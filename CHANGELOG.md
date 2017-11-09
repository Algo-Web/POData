Unreleased
----------

0.3.0 (2017-11-09)
------------------
   * Handle forward slashes in underlying data.  (see [#171] (https://github.com/Algo-Web/POData/pull/171))
   * Add capability to eager-load relations (see [#168] (https://github.com/Algo-Web/POData/pull/168))
   * Robustly handle null payloads (see [#167] (https://github.com/Algo-Web/POData/pull/167))
   * Fix multi-level payload processing (see [#166] (https://github.com/Algo-Web/POData/pull/166))
   * Extend KeyDescriptor to return key values as ODataProperty array.  (see [#165] (https://github.com/Algo-Web/POData/pull/165))
   * Label entry and feed elements correctly when serialising (see [#163] (https://github.com/Algo-Web/POData/pull/163))
   * Bring test coverage up to 97% of production codebase.  Again, this has rumbled numerous bugfixen, cleanups, etc.
   * Add concrete type handling (see [#162] (https://github.com/Algo-Web/POData/pull/162))
   * Add bulk request handling (see [#157] (https://github.com/Algo-Web/POData/pull/157))
   * Deep-six the ObjectSerialiser in favour of its Cynic replacement
        - Whole bunch of individual pull requests building it, polishing it, etc
   * Add support for abstract resource types (see [#140] (https://github.com/Algo-Web/POData/pull/140))
   * Crank up Scrutinizer static analysis (see [#118] (https://github.com/Algo-Web/POData/pull/118))
   * Remix uri processing (see [#112] (https://github.com/Algo-Web/POData/pull/112))
   * Remove long-obsolete Readers folder (see [#103] (https://github.com/Algo-Web/POData/pull/103))
   * Add null/default values to primitive properties (see [#86] (https://github.com/Algo-Web/POData/pull/86))
   * Debork service document (see [#85] (https://github.com/Algo-Web/POData/pull/85))
   * Make relation hookup idempotent (see [#81] (https://github.com/Algo-Web/POData/pull/81))
   * Add bidirectional relations (see [#80] (https://github.com/Algo-Web/POData/pull/80))
   * Hook up new metadata system (see [#75] (https://github.com/Algo-Web/POData/pull/75))
   * Disallow resource/property name collisions (see [#71] (https://github.com/Algo-Web/POData/pull/71))

0.2.0 (2017-03-02)
------------------
   * Correct issues to better provide streamable data.  (see [#68] (https://github.com/Algo-Web/POData/pull/68))
   * Fix set expansion.  **cdcampos** rides again!  (see [#66](https://github.com/Algo-Web/POData/issues/66) and [#67](https://github.com/Algo-Web/POData/pull/67) )
   * Bring test coverage up to 80% of production codebase, not just covered files.  This has rumbled small bugfixen, cleanups, etc that are too numerous to list here.
   * Unify property get/set calls into specialist class.  (see [#61](https://github.com/Algo-Web/POData/pull/61))
   * Refactor base service class to enable serialiser injection in constructor.  (see [#57](https://github.com/Algo-Web/POData/pull/57))
   * Fix Schroedinbug in property retrieval.  (see [#53](https://github.com/Algo-Web/POData/pull/53))
   * Fix Edm.DateTime formatting.  Thanks to **cdcampos** for spotting this.  (see [#50](https://github.com/Algo-Web/POData/issues/50) and [#51](https://github.com/Algo-Web/POData/pull/51))
   * Deep-six production use of create_function to close off an arbitrary-remote-code-execution vulnerability.  (see [#47](https://github.com/Algo-Web/POData/pull/47))
   * Refactor ObjectModelSerializer and its base class.  Not into sanity - not sure that's possible.
   * Deep-six Phockito and convert tests to use Mockery.  (see [#39](https://github.com/Algo-Web/POData/pull/39))
   * Hoisted service examples out to their own repo.  (see [#37](https://github.com/Algo-Web/POData/pull/37))
   * Fix null reference bug in skip token handling.  (see [#33](https://github.com/Algo-Web/POData/pull/33))
   * Changed default service version to maximum supported.  (see [#31](https://github.com/Algo-Web/POData/pull/31))
   * Get resource type round-trip serialisation working properly.  (see [#30](https://github.com/Algo-Web/POData/pull/30))
   * Check property names comply to OData v3 specification.  (see [#26](https://github.com/Algo-Web/POData/pull/26))


0.1.0 (2016-12-22)
------------------

   * Initial release.  Frankensteined together from POData/POData and its descendants.
