<?php
namespace com\google\i18n\phonenumbers;

require_once 'BuildMetadataFromXml.php';

/**
 * Tool to convert phone number metadata from the XML format to protocol buffer format.
 *
 * @author Davide Mendolia
 */
class BuildMetadataPHPFromXml {
	const HELP_MESSAGE = <<<'EOT'
  Usage:
  BuildMetadataProtoFromXml <inputFile> <outputDir> <forTesting> [<liteBuild>]
  
  where:
    inputFile    The input file containing phone number metadata in XML format.
    outputDir    The output source directory to store phone number metadata in proto
                 format (one file per region) and the country code to region code
                 mapping file.
    forTesting   Flag whether to generate metadata for testing purposes or not.
    liteBuild    Whether to generate the lite-version of the metadata (default:
                 false). When set to true certain metadata will be omitted.
                 At this moment, example numbers information is omitted.
  
  Metadata will be stored in:
    <outputDir> META_DATA_FILE_PREFIX . "_*
  Mapping file will be stored in:
    <outputDir>/ PACKAGE_NAME . "/"
  COUNTRY_CODE_TO_REGION_CODE_MAP_CLASS_NAME . ".java
  
  Example command line invocation:
  BuildMetadataProtoFromXml PhoneNumberMetadata.xml src false false
EOT;

	const GENERATION_COMMENT = <<<'EOT'
  /* This file is automatically generated by {@link BuildMetadataProtoFromXml}.
   * Please don't modify it directly.
   */


EOT;

	const META_DATA_FILE_PREFIX = 'PhoneNumberMetadata';
	const TEST_META_DATA_FILE_PREFIX = 'PhoneNumberMetadataForTesting';

	public function start($argc, $argv) {
		if ($argc != 4 && $argc != 5) {
			echo self::HELP_MESSAGE;
			return false;
		}
		$inputFile = $argv[1];
		$outputDir = $argv[2];
		$forTesting = $argv[3] === "true";
		$liteBuild = $argc > 4 && $argv[4] === "true";

		$filePrefix = "";
		if ($forTesting) {
			$filePrefix = $outputDir . self::TEST_META_DATA_FILE_PREFIX;
		} else {
			$filePrefix = $outputDir . self::META_DATA_FILE_PREFIX;
		}
		$metadataCollection = BuildMetadataFromXml::buildPhoneMetadataCollection($inputFile, $liteBuild);

		foreach ($metadataCollection as $metadata) {
			/** @var $phoneMetadata PhoneMetadata */
			$regionCode = $metadata->getId();
			// For non-geographical country calling codes (e.g. +800), use the country calling codes
			// instead of the region code to form the file name.
			if ($regionCode === '001') {
				$regionCode = $metadata->getCountryCode();
			}

			$data = '<?php' . PHP_EOL . 'return ' . var_export($metadata->toArray(), TRUE) . ';';
			file_put_contents($filePrefix . "_" . $regionCode, $data);
		}
		/*

		  Map<Integer, List<String>> countryCodeToRegionCodeMap =
		  BuildMetadataFromXml.buildCountryCodeToRegionCodeMap(metadataCollection);

		  writeCountryCallingCodeMappingToJavaFile(countryCodeToRegionCodeMap, outputDir, forTesting);
		  } catch (Exception e) {
		  e.printStackTrace();
		  return false;
		  }
		  System.out.println("Metadata code successfully generated.");
		  return true;
		 * */
	}

	/*
	  private static final String MAPPING_IMPORTS =
	  "import java . util . ArrayList;
	  \n" +
	  "import java . util . HashMap;
	  \n" +
	  "import java.util.List;
	  \n" +
	  "import java . util . Map;
	  \n";
	  private static final String MAPPING_COMMENT =
	  "  // A mapping from a country code to the region codes which denote the\n" +
	  "  // country/region represented by that country code. In the case of multiple\n" +
	  "  // countries sharing a calling code, such as the NANPA countries, the one\n" +
	  "  // indicated with \"isMainCountryForCode\" in the metadata should be first.\n";
	  private static final double MAPPING_LOAD_FACTOR = 0.75;
	  private static final String MAPPING_COMMENT_2 =
	  "    // The capacity is set to %d as there are %d different country codes,\n" +
	  "    // and this offers a load factor of roughly " + MAPPING_LOAD_FACTOR + ".\n";
	  private static final int COPYRIGHT_YEAR = 2010;

	  private static void writeCountryCallingCodeMappingToJavaFile(
	  Map<Integer, List<String>> countryCodeToRegionCodeMap,
	  String outputDir, boolean forTesting) throws IOException {
	  String mappingClassName;
	  if (forTesting) {
	  mappingClassName = TEST_COUNTRY_CODE_TO_REGION_CODE_MAP_CLASS_NAME;
	  } else {
	  mappingClassName = COUNTRY_CODE_TO_REGION_CODE_MAP_CLASS_NAME;
	  }
	  String mappingFile = outputDir + "/" + PACKAGE_NAME + "/" + mappingClassName + ".java";
	  int capacity = (int) (countryCodeToRegionCodeMap . size() / MAPPING_LOAD_FACTOR);

	  BufferedWriter writer = new BufferedWriter(new FileWriter(mappingFile));

	  CopyrightNotice . writeTo(writer, COPYRIGHT_YEAR);
	  writer . write(GENERATION_COMMENT);
	  if (PACKAGE_NAME . length() > 0) {
	  writer . write("package " + PACKAGE_NAME . replaceAll("/", ".") + ";\n\n");
	  }
	  writer . write(MAPPING_IMPORTS);
	  writer . write("\n");
	  writer . write("public class " + mappingClassName + " {\n");
	  writer . write(MAPPING_COMMENT);
	  writer . write("  static Map<Integer, List<String>> getCountryCodeToRegionCodeMap() {\n");
	  Formatter formatter = new Formatter(writer);
	  formatter . format(MAPPING_COMMENT_2, capacity, countryCodeToRegionCodeMap . size());
	  writer . write("    Map<Integer, List<String>> countryCodeToRegionCodeMap =\n");
	  writer . write("        new HashMap<Integer, List<String>>(" + capacity + ");\n");
	  writer . write("\n");
	  writer . write("    ArrayList<String> listWithRegionCode;\n");
	  writer . write("\n");

	  for (Map.Entry<Integer, List<String>> entry : countryCodeToRegionCodeMap.entrySet()) {
	  int countryCallingCode = entry . getKey();
	  List<String> regionCodes = entry . getValue();
	  writer . write("    listWithRegionCode = new ArrayList<String>(" + regionCodes . size() + ");\n");
	  for (String regionCode : regionCodes) {
	  writer . write("    listWithRegionCode.add(\"" + regionCode + "\");\n");
	  }
	  writer . write("    countryCodeToRegionCodeMap.put(" + countryCallingCode +
	  ", listWithRegionCode);\n");
	  writer . write("\n");
	  }

	  writer . write("    return countryCodeToRegionCodeMap;\n");
	  writer . write("  }\n");
	  writer . write("}\n");

	  writer . flush();
	  writer . close();
	  }
	 *
	 */
}

$buildMetadataPHPFromXml = new BuildMetadataPHPFromXml();
$buildMetadataPHPFromXml->start($argc, $argv);